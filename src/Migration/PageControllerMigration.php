<?php

declare(strict_types=1);

/*
 * This file is part of the Ferienpass package.
 *
 * (c) Richard Henkenjohann <richard@ferienpass.online>
 *
 * For more information visit the project website <https://ferienpass.online>
 * or the documentation under <https://docs.ferienpass.online>.
 */

namespace Ferienpass\HostPortalBundle\Migration;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\MigrationInterface;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\Slug\Slug;
use Doctrine\DBAL\Connection;

class PageControllerMigration implements MigrationInterface
{
    private Connection $connection;
    private Slug $slug;
    private ContaoFramework $framework;

    private static array $pageTypes = [
        'host_edit_offer' => 'Angebot bearbeiten',
        'host_edit_organization' => 'Stammdaten bearbeiten',
        'host_participant_list' => 'Teilnahmeliste',
        'host_change_password' => 'Passwort ändern',
        'host_follow_invitation' => 'Einladung akzeptieren',
        'host_personal_data' => 'Persönliche Daten',
        'host_view_offer' => 'Angebot ansehen',
        'host_view_organization' => 'Stammdaten',
        'host_offer_list' => 'Angebote',
        'host_forgot_password' => 'Passwort vergessen',
    ];

    public function __construct(Connection $connection, Slug $slug, ContaoFramework $framework)
    {
        $this->connection = $connection;
        $this->slug = $slug;
        $this->framework = $framework;
    }

    public function getName(): string
    {
        return 'Introduce page controllers for host portal';
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_page'])) {
            return false;
        }

        // Return true if column has id values
        return $this->connection->query(
                "SELECT * FROM tl_page WHERE type IN ('".implode("','", array_keys(self::$pageTypes))."')"
            )->rowCount() < \count(self::$pageTypes);
    }

    public function run(): MigrationResult
    {
        $this->framework->initialize();

        $rootPage = $this->connection
            ->query("SELECT id FROM tl_page WHERE type='root' AND dns LIKE 'veranstalter%'")
            ->fetchColumn();

        $sorting = $this->connection
            ->executeQuery('SELECT MAX(sorting) FROM tl_page WHERE pid=:pid', ['pid' => $rootPage])
            ->fetchColumn();

        $types = $this->connection
            ->executeQuery("SELECT `type` FROM tl_page WHERE `type` IN ('".implode("','", array_keys(self::$pageTypes))."')")
            ->fetchAllAssociative();
        $types = array_column($types, 'type');

        foreach (array_diff_key(array_keys(self::$pageTypes), $types) as $pageType) {
            $sort = $sorting + 128;
            $title = self::$pageTypes[$pageType];
            $alias = $this->slug->generate($title, $rootPage);

            $this->connection->query(
                "INSERT INTO tl_page (title, `type`, `alias`, pid, published, hide, noSearch, sorting) VALUES ('$title', '$pageType', '$alias', '$rootPage', '1', '1', '1', $sort)"
            )->rowCount();
        }

        return new MigrationResult(true, 'Created pages');
    }
}
