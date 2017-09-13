<?php

namespace AppBundle\Twig;

class AppExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('phone_number', [$this, 'phoneNumberFilter']),
        ];
    }

    public function phoneNumberFilter($number)
    {
        $areaCode = substr($number, 0, 3);
        $first3   = substr($number, 3, 3);
        $last4    = substr($number, -4);

        return '('.$areaCode.') '.$first3.'-'.$last4;
    }
}
