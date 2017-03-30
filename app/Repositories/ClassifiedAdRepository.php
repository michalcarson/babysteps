<?php
namespace App\Repositories;

use App\Models\ClassifiedAd;

class ClassifiedAdRepository extends AbstractRepository
{
    /**
     * @var ClassifiedAd
     */
    private $model;

    public function __construct(ClassifiedAd $model)
    {
        $this->model = $model;
    }

    public function getNew()
    {
        return $this->model;
    }

    public function findActiveAds($email)
    {
        // For now, I'm just going to paste these SQL statements into this file and leave them.
        // We'll get to the implementation later when we bring in the ORM.
        $sql = 'SELECT caID FROM m_classified_ads WHERE caEmail = \''.mysql_escape_string($email).
            '\' AND ( (caDateExpires >= Now() AND caStatus='.AD_APPROVED.')'.
            ' OR (caDateCreated >= SUBDATE(NOW(), INTERVAL 1 DAY) AND caStatus='.AD_SUBMITTED.') )';
    }

    public function deleteOldAds($email)
    {
        $sql = "delete from m_classified_ads where caEmail='".mysql_escape_string($email)."'"
            ." AND caDateExpires < Now() ";
    }
}
