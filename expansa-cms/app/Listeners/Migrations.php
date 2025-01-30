<?php

declare(strict_types=1);

namespace App\Listeners;

use Expansa\Database\Schema;
use Expansa\Database\Schema\Table;
use Expansa\Support\Str;

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
            $table->bigInt('author_id')->unsigned()->default(0);
            $table->bigInt('parent_id')->unsigned()->default(0);
            $table->smallInt('comments')->unsigned()->default(0);
            $table->smallInt('views')->unsigned()->default(0);
            $table->mediumInt('position')->unsigned()->default(0);
            $table->enum('status', $statuses)->default('pending');
            $table->bool('discussable')->default(1);
            $table->string('password', 255);
            $table->timestamps();

            // indexes
            $table->index('author_id');
            $table->index('parent_id');
            $table->index('status');
            $table->index(['title', 'content']); // TODO: fulltext index
        });

        $this->createFieldsTable($postType);
    }

    private function createFieldsTable(string $name): void
    {
        Schema::create($name . '_fields', function (Table $table) use ($name) {
            $column = sprintf("%s_id", Str::singularize($name));

            $table->id();
            $table->bigInt($column)->unsigned()->default(0);
            $table->string('key', 255)->default(null);
            $table->mediumText('value');

            // indexes
            $table->index([$column, 'key']);
            $table->foreign($column)->references('id')->on($name)->onDeleteCascade();
        });
    }

    private function createCacheTable(): void
    {
        Schema::create('cache', function (Table $table) {
            $table->string('key', 191)->primary();
            $table->mediumText('value');
            $table->datetime('expiry_at');

            // indexes
            $table->index('expiry_at');
        });
    }

    private function createSlugsTable(): void
    {
        Schema::create('slugs', function (Table $table) {
            $table->id();
            $table->bigInt('post_id')->unsigned();
            $table->string('post_table', 255);
            $table->ulid()->unique();
            $table->string('slug', 255);
            $table->string('locale', 10)->nullable()->default(null);

            // indexes
            $table->unique(['slug', 'locale']);
            $table->unique(['post_id', 'post_table']);
        });
    }

    private function createTermsTable(): void
    {
        Schema::create('terms', function (Table $table) {
            $table->id();
            $table->string('name', 255)->default('');
            $table->string('slug', 255)->default('');
            $table->bigInt('term_group')->unsigned()->default(0);

            // indexes
            $table->index('slug', 'slug_index');
            $table->index('name', 'name_index');
        });

        $this->createFieldsTable('terms');
    }

    private function createUsersTable(): void
    {
        Schema::create('users', function (Table $table) {
            $table->id();
            $table->ulid()->unique();
            $table->string('login', 60)->unique();
            $table->string('password', 255);
            $table->string('nicename', 60);
            $table->string('firstname', 60);
            $table->string('lastname', 60);
            $table->string('showname', 255);
            $table->string('email', 100)->unique();
            $table->string('locale', 10)->nullable()->default(null);
            $table->bool('is_verified')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('verification_token', 100);
            $table->string('password_reset_token', 100);
            $table->dateTime('visited_at')->useCurrent();
            $table->timestamps();

            // indexes
            $table->index('nicename');
        });

        $this->createFieldsTable('users');
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
            $table->string('locale', 10)->nullable()->default(null);
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
            $table->index('locale');
            $table->index('status');
            $table->index('likes');
            $table->index('dislikes');
            $table->index('rating');
            $table->index('created_at');
        });
    }

    private function createOptionsTable(): void
    {
        Schema::create('options', function (Table $table) {
            $table->id();
            $table->string('key', 191)->unique()->default('');
            $table->text('value');
        });
    }

    private function createTaxonomiesTable(): void
    {
        Schema::create('taxonomies', function (Table $table) {
            $table->id();
            $table->bigInt('term_id')->unsigned()->default(0);
            $table->bigInt('count')->unsigned()->default(0);
            $table->bigInt('parent')->unsigned()->default(0);

            // indexes
            $table->index('term_id');
        });
    }
}
