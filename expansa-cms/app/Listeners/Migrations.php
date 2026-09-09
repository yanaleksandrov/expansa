<?php

declare(strict_types=1);

namespace App\Listeners;

use Expansa\Database\Schema;
use Expansa\Database\Schema\Table;
use Expansa\Support\Str;

final class Migrations
{
    public const array VISIBILITY_STATUSES = ['publish', 'pending', 'draft', 'protected', 'private', 'trash', 'future'];

    public const array DISCUSSIONS_STATUSES = ['open', 'closed'];

    public function createMainDatabaseTables(): void
    {
        $this->createCacheTable();
        $this->createSlugsTable();
        $this->createTermsTable();
        $this->createUsersTable();
        $this->createOptionsTable();
        $this->createCommentsTable();
        $this->createTaxonomiesTable();
        $this->createMediaTable();
        $this->createApiKeysTable();
    }

    public function createPostsTable(string $postType): void
    {
        Schema::create($postType, function (Table $table) {
            $table->id();
            $table->uuid()->unique();
            $table->text('title');
            $table->text('content');
            $table->bigInt('author_id')->unsigned()->default(0);
            $table->bigInt('parent_id')->unsigned()->default(0);
            $table->smallInt('comments')->unsigned()->default(0);
            $table->smallInt('views')->unsigned()->default(0);
            $table->mediumInt('position')->unsigned()->default(0);
            $table->enum('status', self::VISIBILITY_STATUSES)->default('pending');
            $table->enum('discussion', self::DISCUSSIONS_STATUSES)->default('open');
            $table->string('password', 255);
            $table->timestamps();

            // indexes
            $table->index('author_id');
            $table->index('parent_id');
            $table->index('status');
            $table->fulltext(['title', 'content']);
        });

        $this->createFieldsTable($postType, 'posts');
    }

    private function createFieldsTable(string $name, ?string $idColumnName = null): void
    {
        Schema::create($name . '_fields', function (Table $table) use ($name, $idColumnName) {
            $column = sprintf("%s_id", Str::singularize($idColumnName ?? $name));

            $table->id();
            $table->bigInt($column)->unsigned()->default(0);
            $table->string('key', 255)->default(null);
            $table->mediumText('value');

            // indexes
            $table->index([$column, 'key']);
            $table->foreign($column)->references('id')->on($name)->onDeleteCascade();
        });
    }

    /**
     * The four typed sibling tables for {@see \App\Models\TypedField} — see there for what each
     * one is for. Additive to {@see createFieldsTable()}, never a replacement for it: a model
     * using {@see \Expansa\Database\Model\HasFieldTyped} keeps its regular "{name}_fields" table
     * too, for everything that doesn't need indexed filtering/sorting/search.
     */
    private function createTypedFieldsTable(string $name, ?string $idColumnName = null): void
    {
        $column = sprintf("%s_id", Str::singularize($idColumnName ?? $name));

        Schema::create($name . '_fields_scalar', function (Table $table) use ($name, $column) {
            $table->id();
            $table->bigInt($column)->unsigned()->default(0);
            $table->string('key', 255)->default(null);
            $table->bigInt('value_int')->nullable();
            $table->decimal('value_decimal', 20, 6)->nullable();

            $table->index([$column, 'key']);
            $table->index(['key', 'value_int']);
            $table->index(['key', 'value_decimal']);
            $table->foreign($column)->references('id')->on($name)->onDeleteCascade();
        });

        Schema::create($name . '_fields_datetime', function (Table $table) use ($name, $column) {
            $table->id();
            $table->bigInt($column)->unsigned()->default(0);
            $table->string('key', 255)->default(null);
            $table->datetime('value');

            $table->index([$column, 'key']);
            $table->index(['key', 'value']);
            $table->foreign($column)->references('id')->on($name)->onDeleteCascade();
        });

        Schema::create($name . '_fields_varchar', function (Table $table) use ($name, $column) {
            $table->id();
            $table->bigInt($column)->unsigned()->default(0);
            $table->string('key', 255)->default(null);
            $table->string('value', 255);

            $table->index([$column, 'key']);
            $table->index(['key', 'value']);
            $table->foreign($column)->references('id')->on($name)->onDeleteCascade();
        });

        Schema::create($name . '_fields_text', function (Table $table) use ($name, $column) {
            $table->id();
            $table->bigInt($column)->unsigned()->default(0);
            $table->string('key', 255)->default(null);
            $table->mediumText('value');

            $table->index([$column, 'key']);
            $table->fulltext('value');
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
            $table->bigInt('entity_id')->unsigned();
            $table->string('entity_table', 255);
            $table->string('slug', 255);
            //$table->string('locale', 10)->nullable()->default(null);

            // indexes
            $table->unique(['slug']);
            $table->unique(['entity_id', 'entity_table']);
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
            $table->uuid()->unique();

            // authentication
            $table->string('login', 60)->unique();
            $table->string('password', 255);

            // profile
            $table->string('nicename', 60)->nullable();
            $table->string('firstname', 60)->nullable();
            $table->string('lastname', 60)->nullable();
            $table->string('showname', 255)->nullable();
            $table->string('email', 100)->unique();
            $table->string('locale', 10)->nullable()->default(null);

            // status and verification
            $table->json('roles')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->bool('is_verified')->default(0);

            // tokens for email verification and password reset
            $table->string('verification_token', 100)->nullable();
            $table->timestamp('verification_token_expires_at')->nullable();
            $table->string('password_reset_token', 100)->nullable();
            $table->timestamp('password_reset_expires_at')->nullable();

            // activity tracking
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();

            // indexes
            $table->index('nicename');
            $table->index('deleted_at');
        });

        $this->createFieldsTable('users');
        $this->createTypedFieldsTable('users');
    }

    private function createCommentsTable(): void
    {
        Schema::create('comments', function (Table $table) {
            $table->id();
            $table->bigInt('post_id')->unsigned()->default(0);
            $table->string('post_type', 64)->default('');
            $table->bigInt('parent_id')->unsigned()->default(0);
            $table->bigInt('author_id')->unsigned()->default(0);
            $table->string('author_name', 64)->default('');
            $table->string('author_email', 255)->default('');
            $table->string('author_ip', 39)->default('');
            $table->string('author_agent', 255)->default('');
            $table->string('locale', 10)->nullable()->default(null);
            $table->enum('status', ['pending', 'approved', 'spam', 'rejected'])->default('pending');
            $table->text('content');
            $table->smallInt('likes')->unsigned()->default(0);
            $table->smallInt('dislikes')->unsigned()->default(0);
            $table->smallInt('rating')->unsigned()->nullable()->default(null);
            $table->timestamps();
            $table->timestamp('deleted_at');

            // indexes
            $table->index('post_id');
            $table->index('post_type');
            $table->index('parent_id');
            $table->index('author_id');
            $table->index('locale');
            $table->index('status');
            $table->index('likes');
            $table->index('dislikes');
            $table->index('rating');
            $table->index('created_at');
            $table->index('updated_at');
            $table->index('deleted_at');
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

    private function createMediaTable(): void
    {
        Schema::create('media', function (Table $table) {
            $table->id();

            $table->string('filename');
            $table->string('disk', 50)->default('local');
            $table->string('path');

            // File information
            $table->string('mime_type', 100);
            $table->string('extension', 20)->nullable();
            $table->bigInt('size')->unsigned()->default(0);
            $table->string('hash', 64)->nullable(); // for check duplicates

            // Image/video information
            $table->int('width')->unsigned()->nullable();
            $table->int('height')->unsigned()->nullable();
            $table->int('duration')->unsigned()->nullable();

            $table->string('title')->nullable();
            $table->text('caption')->nullable();
            $table->text('description')->nullable();
            $table->string('alt')->nullable();

            // Ownership
            $table->bigInt('user_id')->nullable();

            // File status
            $table->enum('status', self::VISIBILITY_STATUSES)->default('published');
            $table->enum('discussion', self::DISCUSSIONS_STATUSES)->default('open');
            $table->string('password', 255);

            $table->timestamps();

            // indexes
            $table->index('mime_type');
            $table->index('user_id');
            $table->index('status');
            $table->index('hash');
        });
    }

    private function createApiKeysTable(): void
    {
        Schema::create('api_keys', function (Table $table) {
            $table->id();

            $table->bigInt('user_id')->unsigned();

            $table->string('name');
            $table->string('prefix', 12);
            $table->string('key_hash', 64)->unique();

            $table->bool('active')->default(true);

            $table->json('abilities')->nullable();

            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('active');
        });
    }
}
