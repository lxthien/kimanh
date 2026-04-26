<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class ConstructionMaterialRepository extends EntityRepository
{
    public function findActiveOrdered()
    {
        return $this->createQueryBuilder('m')
            ->where('m.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('m.category', 'ASC')
            ->addOrderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getPriceMap()
    {
        $items = $this->createQueryBuilder('m')
            ->where('m.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        $map = array();
        foreach ($items as $item) {
            $materialData = array(
                'name' => $item->getName(),
                'category' => $item->getCategory(),
                'unit' => $item->getUnit(),
                'unitPrice' => (float) $item->getUnitPrice(),
                'wastagePercent' => (float) $item->getWastagePercent(),
                'note' => $item->getNote(),
                'sourceCode' => strtoupper(trim($item->getCode())),
            );

            $code = strtoupper(trim($item->getCode()));
            $map[$code] = $materialData;

            foreach ($this->getCodeAliases($code) as $alias) {
                if (!isset($map[$alias])) {
                    $map[$alias] = $materialData;
                }
            }
        }

        return $map;
    }

    private function getCodeAliases($code)
    {
        $aliases = array(
            'BT' => array('BETONG'),
            'BETONG' => array('BT'),
            'TH' => array('THEP'),
            'THEP' => array('TH'),
            'GX' => array('GACH'),
            'GACH' => array('GX'),
            'CATXAY' => array('CAT'),
            'CAT' => array('CATXAY'),
            'SONNUOC' => array('SON'),
            'SON' => array('SONNUOC'),
            'GACHLATNEN' => array('GACHLAT'),
            'GACHLAT' => array('GACHLATNEN'),
            'DD' => array('DAYDIEN'),
            'DAYDIEN' => array('DD'),
            'ON' => array('ONGNUOC'),
            'ONGNUOC' => array('ON'),
        );

        return isset($aliases[$code]) ? $aliases[$code] : array();
    }
}
