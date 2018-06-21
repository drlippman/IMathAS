<?php


class AdminLoginCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    public function adminLoginTest(AcceptanceTester $I)
    {
        $I->wantTo('Login as an administrator');
        $I->amOnPage('/');
        $I->fillField('username', 'root');
        $I->fillField('password', 'rootroot');
        $I->click('Login');
        $I->see('Log Out');
    }
}
