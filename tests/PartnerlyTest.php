<?php

namespace Services\Promo;

use Partnerly\ApplierInterface;
use Partnerly\Exceptions\CodeUsedException;
use Partnerly\Exceptions\InvalidCodeException;
use Partnerly\Partnerly;
use Partnerly\PromoCode;
use Partnerly\SecurityClient;
use Partnerly\ValidatorInterface;
use PHPUnit\Framework\TestCase;

class PartnerlyTest extends TestCase
{

    public function testVerPromo()
    {
        $partnerly = $this->getPartnerLy();

        $partnerly->useCode($promoCode->code, $mobile);
        $codeResult = $partnerly->getCode($promoCode->code, $mobile->getMd());

        $this->assertEquals(1, $codeResult->usedCount);

        return $codeResult;
    }

    /**
     * @depends testVerPromo
     * @param PromoCode $promoCode
     * @expectedException CodeUsedException
     * @return PromoCode
     */
    public function testOneTime(PromoCode $promoCode)
    {
        $partnerly = $this->getPartnerLy();
        $partnerly->useCode($promoCode->code, $context);
        return $promoCode;
    }

    /**
     * @depends testVerPromo
     * @param PromoCode $promoCode
     * @expectedException InvalidCodeException
     */
    public function testDeletePromo(PromoCode $promoCode)
    {
        $partnerly = $this->getPartnerLy();
        $partnerly->applyCode($promoCode->code, $context);
    }

    /**
     * @return Partnerly
     */
    private function getPartnerLy()
    {
        $applier = new class implements ApplierInterface {
            public function apply(PromoCode $promoCode, $context)
            {
            }
        };
        $validator = new class implements ValidatorInterface {
            public function validate(PromoCode $code, $context)
            {
            }
        };

        $securityClient = $this->prophesize(SecurityClient::class);

        $partnerly = new Partnerly($applier, $validator, '', 'test', 'secret_test');
        return $partnerly->setSecurityClient($securityClient->reveal());
    }
}
