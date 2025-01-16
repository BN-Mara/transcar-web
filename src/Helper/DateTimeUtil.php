<?php
namespace App\Helper;

class DateTimeUtil {  

    public static function now():DateTime {
        return new \DateTime('now',new \DateTimeZone('Africa/Kinshasa'));
    }
}
