<?php

/*
 * This file is part of the Tabler bundle, created by Kevin Papst (www.kevinpapst.de).
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Codyas\SkeletonBundle\Helper;

interface Constants
{
    public const TYPE_SUCCESS = 'success';
    public const TYPE_WARNING = 'warning';
    public const TYPE_ERROR = 'danger';
    public const TYPE_INFO = 'info';
    public const ACTION_LIST = 'list';
    public const ACTION_DETAILS = 'details';
    public const ACTION_CREATE = 'create';
    public const ACTION_EDIT = 'edit';
    public const ACTION_DELETE = 'delete';
    public const SUBMIT_TYPE_APPLY = 'apply';
    public const SUBMIT_TYPE_SAVE = 'save';
    public const FLASH_NOT_BLOCKING_ALERTS = 'csk-non-blocking-alerts';
    public const FLASH_ALERTS = 'csk-alerts';
    public const ALERTS_SUCCESS = 'csk-alerts-success';
}
