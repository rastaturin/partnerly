<?php

namespace Partnerly;

interface ApplierInterface
{
    public function apply(PromoCode $promoCode, Context $context);
}