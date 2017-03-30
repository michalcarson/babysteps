<?php
namespace App\Repositories;

class ClassifiedCategoryRepository extends AbstractRepository
{
    public function findByGroup($selectedGroup)
    {
        $sql = sprintf("SELECT ccID, ccName FROM m_classified_cats WHERE f_cgID = %s ORDER BY ccName", $selectedGroup);
    }
}
