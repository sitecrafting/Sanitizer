<?php
namespace Pegasus\Columns\Mock;

use Pegasus\Columns\Mock;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 19/05/15
 * Time: 11:37
 */
class Email extends AbstractMockData
{
    /**
     * Returns sanitised street names
     *
     * @return array
     */
    public function getValues()
    {
        static $emails = null;
        if(null == $emails)
        {
            $firstNames     = new FirstName();
            $lastNames      = new LastName();
            $tlds           = array('gmail.com', 'gotmail.com', 'woosa.com', 'notanaddress.com');
            $names = array_merge($firstNames->getValues(), $lastNames->getValues());
            foreach($names as $name)
            {
                foreach ($tlds as $tld)
                {
                    $emails[] = $name.rand(0, 100000).'@'.$tld;

                }
            }
        }
        return $emails;
    }

    /**
     * All E-mails must be different!
     * @return bool
     */
    public function supportsMassUpdate()
    {
        return false;
    }
}