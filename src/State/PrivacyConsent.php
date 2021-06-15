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

namespace Ferienpass\HostPortalBundle\State;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Michelf\MarkdownExtra;

class PrivacyConsent
{
    private string $consentText;
    private Connection $connection;
    private ContaoFramework $framework;

    public function __construct(ContaoFramework $framework, Connection $connection, string $consentText)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->consentText = $consentText;
    }

    public function getFormattedConsentText(): string
    {
        if ('' === $this->consentText) {
            return '';
        }

        $config = $this->framework->getAdapter(Config::class);

        /* @noinspection PhpUndefinedMethodInspection */
        return strip_tags(MarkdownExtra::defaultTransform($this->consentText), $config->get('allowedTags'));
    }

    public function isSignedFor(int $memberId): bool
    {
        if ('' === $this->consentText) {
            return true;
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('tstamp')
            ->from('tl_ferienpass_host_privacy_consent')
            ->where('member=:member')
            ->andWhere('type="sign"')
            ->andWhere('statement_hash=:hash')
            ->setParameter('member', $memberId)
            ->setParameter('hash', sha1($this->getFormattedConsentText()))
            ->setMaxResults(1)
            ->orderBy('tstamp', 'DESC')
            ->execute();

        return false !== $statement->fetchColumn();
    }

    public function hashIsValid(string $actualTextHash): bool
    {
        return hash_equals($actualTextHash, sha1($this->getFormattedConsentText()));
    }
}
