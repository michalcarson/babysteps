<?php

namespace App\Models;

class ClassifiedAd extends AbstractModel
{
    function __construct($id = null)
    {
        parent::__construct();

        $this->setTable('m_classified_ads');
        $this->setIdentifierField('caID');

        if ( ! is_null($id)) {
            $this->setId($id);
        }
    }

}
