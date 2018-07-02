<?php

namespace Partnerly;

class PromoCode
{
    const EXPIRE = 'expire';
    const VER = 'ver';
    const REFERRAL = 'referral';
    const CUSTOMER_REFERRAL = 'customer_referral';

    const ACTION_ADDDAYS = 'add-days';
    const ACTION_REFERRAL = 'referral';

    public $expiration;
    public $referral;
    public $used;
    public $used_count;
    public $usage;
    public $usedCount;
    public $code;
    public $type;
    public $conditions = [];
    public $action = [];
    public $description;
    public $contextUsage = [];
    public $referrals = [];
    public $one_time = false;

    /**
     * PromoCode constructor.
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->populate($data);
        $this->action = json_decode($this->action);
        $this->conditions = json_decode($this->conditions);
    }

    /**
     * @return bool
     */
    public function isReferral()
    {
        return $this->type == self::REFERRAL;
    }

    public function isCustomerReferral()
    {
        return $this->type == self::CUSTOMER_REFERRAL;
    }

    /**
     * Populate fields from array
     * @param $data
     */
    public function populate($data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @param string $name
     * @param mixed $params
     * @return $this
     */
    public function setAction($name, $params)
    {
        $this->action[$name] = $params;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $params
     * @return $this
     */
    public function setCondition($name, $params)
    {
        $this->conditions[$name] = $params;
        return $this;
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        } else {
            $this->extraData[$name] = $value;
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return (array) $this;
    }

    public function getUser()
    {
        return $this->referral['user'] ?? null;
    }

    public function getInnerId()
    {
        return $this->referral['inner_id'] ?? null;
    }

    public function alreadyUsed()
    {
        return $this->used || ($this->used_count && $this->one_time);
    }
}