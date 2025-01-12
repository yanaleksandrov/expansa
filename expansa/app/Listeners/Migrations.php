<?php

declare(strict_types=1);

namespace app\Listeners;

use Expansa\Database\Db;
use Expansa\Database\Schema;
use Expansa\Database\Schema\Table;

final class Migrations
{
    public function createMainDatabaseTables(): void
    {
        $this->createCacheTable();
        $this->createSlugsTable();
        $this->createTermsTable();
        $this->createUsersTable();
        $this->createOptionsTable();
        $this->createCommentsTable();
        $this->createTaxonomiesTable();
    }

    public function createPostsTable(string $postType): void
    {
        Schema::create($postType, function (Table $table) {
            $statuses = ['publish', 'pending', 'draft', 'protected', 'private', 'trash', 'future'];

            $table->id();
            $table->text('title');
            $table->text('content');
            $table->bigInt('author_id')->default(0);
            $table->bigInt('parent_id')->default(0);
            $table->smallInt('comments')->default(0);
            $table->smallInt('views')->default(0);
            $table->enum('status', $statuses)->default('pending');
            $table->bool('discussable')->default(1);
            $table->string('password', 255);
            $table->timestamps();

            // indexes
            $table->index('author_id');
            $table->index('parent_id');
            $table->index('status');
            $table->index('parent_id');
        });

        $this->createFieldsTable($postType);
    }

    public function createFieldsTable(string $name): void
    {
        Schema::create($name . '_fields', function (Table $table) use ($name) {
            $column = "{$name}_id";

            $table->id();
            $table->bigInt($column)->default(0);
            $table->string('key', 255)->default(null);
            $table->text('value');

            // indexes
            $table->index([$column, 'key']);
        });

        $this->addCascadeDeleteTrigger($name);
    }

    private function createCommentsTable(): void
    {
        Schema::create('comments', function (Table $table) {
            $table->id();
            $table->bigInt('post_id')->unsigned()->default(0);
            $table->bigInt('parent_id')->unsigned()->default(0);
            $table->bigInt('author_id')->unsigned()->default(0);
            $table->string('author_name', 64)->default('');
            $table->string('author_email', 255)->default('');
            $table->string('author_ip', 39)->default('');
            $table->string('author_agent', 255)->default('');
            $table->string('type', 20)->default('comment');
            $table->enum('status', ['pending', 'approved', 'spam', 'rejected', 'trash'])->default('pending');
            $table->text('content');
            $table->smallInt('likes')->unsigned()->default(0);
            $table->smallInt('dislikes')->unsigned()->default(0);
            $table->smallInt('rating')->unsigned()->nullable()->default(null);
            $table->timestamps();

            // indexes
            $table->index('post_id');
            $table->index('parent_id');
            $table->index('author_id');
            $table->index('type');
            $table->index('status');
            $table->index('likes');
            $table->index('dislikes');
            $table->index('rating');
            $table->index('created_at');
        });

        $this->createFieldsTable('comments');
    }

    private function createOptionsTable(): void
    {
        Schema::create('options', function (Table $table) {
            $table->id();
            $table->string('key', 191)->default('');
            $table->text('value');
        });
    }

    private function createSlugsTable(): void
    {
        Schema::create('slugs', function (Table $table) {
            $table->id();
            $table->string('ulid')->unique();
            $table->bigInt('post_id')->unsigned();
            $table->string('post_table', 255);
            $table->string('slug', 255)->unique();
            $table->string('locale', 100)->default('');

            // indexes
            $table->index('ulid');
            $table->index(['post_id', 'post_table']);
            $table->index(['slug', 'locale']);
        });

        $this->addUlidCreationTrigger('slugs');
    }

    private function createTermsTable(): void
    {
        Schema::create('terms', function (Table $table) {
            $table->id();
            $table->string('name', 200)->default('');
            $table->string('slug', 200)->default('');
            $table->bigInt('term_group')->default(0);

            // indexes
            $table->index('slug', 'slug_index');
            $table->index('name', 'name_index');
        });

        $this->createFieldsTable('terms');
    }

    private function createTaxonomiesTable(): void
    {
        Schema::create('taxonomies', function (Table $table) {
            $table->id();
            $table->bigInt('term_id')->default(0);
            $table->bigInt('count')->default(0);
            $table->bigInt('parent')->default(0);

            // indexes
            $table->unique('term_id');
        });
    }

    private function createUsersTable(): void
    {
        Schema::create('users', function (Table $table) {
            $table->id();
            $table->string('ulid')->unique();
            $table->string('login', 60);
            $table->string('password', 255);
            $table->string('nicename', 60);
            $table->string('firstname', 60);
            $table->string('lastname', 60);
            $table->string('showname', 255);
            $table->string('email', 100);
            $table->string('locale', 16);
            $table->bool('is_verified')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('verification_token', 100);
            $table->string('password_reset_token', 100);
            $table->dateTime('visited_at')->useCurrent();
            $table->timestamps();

            // indexes
            $table->index('login');
            $table->index('nicename');
            $table->index('email');
            $table->index('status');
        });

        $this->addUlidCreationTrigger('users');

        $this->createFieldsTable('users');
    }

    private function createCacheTable(): void
    {
        Schema::create('cache', function (Table $table) {
            $table->string('key', 255)->primary();
            $table->text('value');
            $table->datetime('expiry_at');

            // indexes
            $table->index('key');
            $table->index('expiry_at');
        });
    }

    private function addUlidCreationTrigger(string $table): void
    {
        $prefix = EX_DB_PREFIX;

        Db::statement(
            "
            CREATE TRIGGER before_insert_$prefix{$table}
                BEFORE INSERT ON $prefix{$table}
                FOR EACH ROW
                    BEGIN
                        IF NEW.ulid IS NULL THEN
                            SET NEW.ulid = UPPER(
                                CONCAT(
                                    LPAD(HEX(UNIX_TIMESTAMP(CURTIME(4)) * 1000), 12, '0'),
                                    HEX(RANDOM_BYTES(10))
                                )
                            );
                        END IF;
                    END;",
        );
    }

    private function addCascadeDeleteTrigger(string $table): void
    {
        $prefix = EX_DB_PREFIX;

        Db::statement(
            "
            CREATE TRIGGER cascade_delete_$prefix{$table}
                AFTER DELETE ON $prefix{$table}
                FOR EACH ROW
                    BEGIN
                        DELETE FROM $prefix{$table}_fields WHERE {$table}_id = OLD.id;
                    END;",
        );
    }
}
