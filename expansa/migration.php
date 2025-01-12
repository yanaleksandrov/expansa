<?php

declare(strict_types=1);

use Expansa\Database\Db;
use Expansa\Database\Schema;
use Expansa\Database\Schema\Table;

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

Schema::create('options', function (Table $table) {
    $table->id();
    $table->string('key', 191)->default('');
    $table->text('value');
});

Schema::create('terms', function (Table $table) {
    $table->id('term_id');
    $table->string('name', 200)->default('');
    $table->string('slug', 200)->default('');
    $table->bigInt('term_group')->default(0);

    // indexes
    $table->index('slug', 'slug_index');
    $table->index('name', 'name_index');
});

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
    $table->string('remember_token', 100);
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

Db::unprepared(
    "
    CREATE TRIGGER before_insert_expansa_users
        BEFORE INSERT ON expansa_users
        FOR EACH ROW
            BEGIN
                IF NEW.ulid IS NULL THEN
                    SET NEW.ulid = CONCAT(UNHEX(CONV(ROUND(UNIX_TIMESTAMP(CURTIME(4))*1000), 10, 16)), RANDOM_BYTES(10));
                END IF;
            END;"
);

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

Db::statement(
    "
        CREATE TRIGGER before_insert_expansa_slugs
            BEFORE INSERT ON expansa_slugs
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
                END;"
);
