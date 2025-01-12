<?php

declare(strict_types=1);

namespace app\Listeners;

use Expansa\Db;

class Migrations
{
    public function createMainDatabaseTables(): void
    {
        Db::query(
            "
			CREATE TABLE IF NOT EXISTS {$tableName} (
				id           bigint(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
				post_id      bigint(20)   UNSIGNED NOT NULL DEFAULT '0',
				author       tinytext     NOT NULL,
				author_id    bigint(20)   UNSIGNED NOT NULL DEFAULT '0',
				author_email varchar(100) NOT NULL DEFAULT '',
				author_url   varchar(200) NOT NULL DEFAULT '',
				author_IP    varchar(100) NOT NULL DEFAULT '',
				dating       datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
				content      text         NOT NULL,
				karma        int(11)      NOT NULL DEFAULT '0',
				approved     varchar(20)  NOT NULL DEFAULT '1',
				agent        varchar(255) NOT NULL DEFAULT '',
				category     varchar(20)  NOT NULL DEFAULT 'comment',
				parent       bigint(20)   UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY  (id),
				KEY post_id (post_id),
				KEY approved_dating (approved,dating),
				KEY dating (dating),
				KEY parent (parent),
				KEY author_email (author_email(10))
			) ENGINE=InnoDB {$charsetCollate};"
        )->fetchAll();
    }
}
