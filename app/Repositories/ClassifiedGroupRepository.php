<?php
namespace App\Repositories;

class ClassifiedGroupRepository extends AbstractRepository
{
    public function getAll()
    {
        // For now, I'm just going to paste these SQL statements into this file and leave them.
        // We'll get to the implementation later when we bring in the ORM.
        $sql = "SELECT * FROM m_classified_groups";
    }
}
