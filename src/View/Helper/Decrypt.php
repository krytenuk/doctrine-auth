<?php

namespace FwsDoctrineAuth\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use FwsDoctrineAuth\Model\Crypt;

/**
 * Decrypt
 *
 * @author Garry Childs (info@freedomwebservices.net)
 */
class Decrypt extends AbstractHelper
{

    /**
     *
     * @var Crypt
     */
    private Crypt $crypt;

    function __construct(array $config)
    {
        $this->crypt = new Crypt($config);
    }

    public function __invoke($encrypted)
    {
        return $this->crypt->rsaDecrypt($encrypted);
    }

}
