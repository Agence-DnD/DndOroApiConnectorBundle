<?php

namespace Dnd\Bundle\DndOroApiConnectorBundle\Services;

/**
 * Class DndOroApiAlexa
 *
 * @package   Dnd\Bundle\DndOroApiConnectorBundle\Services
 * @author    Auriau Maxime <maxime.auriau@dnd.fr>
 * @copyright Copyright (c) 2017 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.dnd.fr/
 */
class DndOroApiAlexa
{
    /**
     * @param $date
     * @return string $date
     */
    public function checkAlexaDate($date)
    {
        /** @var string $currentYear */
        $currentYear = date('Y');
        /** @var array $explodedDate */
        $explodedDate = explode('-', $date);
        if (false !== strpos($date, 'W')) {
            /** @var string $week */
            $week = $explodedDate[1];
            /** @var string $year */
            $year = $explodedDate[0];
            if ($year > $currentYear) {
                /** @var string $year */
                $year = $currentYear;
            }
            /** @var string $date */
            $date = date('Y-m-d', strtotime($year.$week.'7'));
        } else {
            /** @var string $year */
            $year = $explodedDate[0];
            if ($year > $currentYear) {
                $date = new \DateTime($date);
                $date->modify('- 1 year');
                $date = $date->format('Y-m-d');
            }
        }

        return $date;
    }

    /**
     * @param mixed $values
     * @return bool
     */
    public function isValuesNull($values)
    {
        if (is_array($values)) {
            foreach ($values as $value) {
                if (null === $value) {
                    return true;
                }
            }
        }

        if (null === $values) {
            return true;
        }

        return false;
    }
}
