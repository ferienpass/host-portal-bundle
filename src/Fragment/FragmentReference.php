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

namespace Ferienpass\HostPortalBundle\Fragment;

use Ferienpass\CoreBundle\Fragment\FragmentReference as CoreFragmentReference;

class FragmentReference extends CoreFragmentReference
{
    public const TAG_NAME = 'ferienpass.fragment.host';
}
