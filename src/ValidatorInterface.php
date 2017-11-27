<?php

namespace Partnerly;

interface ValidatorInterface
{
    public function validate(PromoCode $code, Context $context);
}