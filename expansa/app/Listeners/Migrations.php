<?php

declare(strict_types=1);

namespace app\Listeners;

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
        Schema::createIfNotExists($postType, function (Table $table) {
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

    private function createFieldsTable(string $name): void
    {
        Schema::createIfNotExists($name . '_fields', function (Table $table) use ($name) {
            $column = "{$name}_id";

            $table->id();
            $table->bigInt($column)->default(0);
            $table->string('key', 255)->default(null);
            $table->text('value');

            // indexes
            $table->index([$column, 'key']);
            $table->foreign($column)->references('id')->on($name)->onDeleteCascade();
        });
    }

    private function createCacheTable(): void
    {
        Schema::createIfNotExists('cache', function (Table $table) {
            $table->string('key', 191)->primary();
            $table->text('value');
            $table->datetime('expiry_at');

            // indexes
            $table->index('key');
            $table->index('expiry_at');
        });
    }

    private function createSlugsTable(): void
    {
        Schema::createIfNotExists('slugs', function (Table $table) {
            $table->id();
            $table->ulid();
            $table->bigInt('post_id')->unsigned();
            $table->string('post_table', 255);
            $table->string('slug', 255)->unique();
            $table->string('locale', 100)->default('');

            // indexes
            $table->index('ulid');
            $table->index(['post_id', 'post_table']);
            $table->index(['slug', 'locale']);
        });
    }

    private function createTermsTable(): void
    {
        Schema::createIfNotExists('terms', function (Table $table) {
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

    private function createUsersTable(): void
    {
        Schema::createIfNotExists('users', function (Table $table) {
            $table->id();
            $table->ulid();
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

        $this->createFieldsTable('users');
    }

    private function createCommentsTable(): void
    {
        Schema::createIfNotExists('comments', function (Table $table) {
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
        Schema::createIfNotExists('options', function (Table $table) {
            $table->id();
            $table->string('key', 191)->default('');
            $table->text('value');
        });
    }

    private function createTaxonomiesTable(): void
    {
        Schema::createIfNotExists('taxonomies', function (Table $table) {
            $table->id();
            $table->bigInt('term_id')->default(0);
            $table->bigInt('count')->default(0);
            $table->bigInt('parent')->default(0);

            // indexes
            $table->index('term_id');
        });
    }
}
