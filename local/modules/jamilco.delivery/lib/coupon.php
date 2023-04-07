<?
namespace Jamilco\Delivery;

use \Bitrix\Main\Loader,
    \Bitrix\Sale\Internals\DiscountTable,
    \Bitrix\Sale\Internals\DiscountCouponTable,
    \Bitrix\Main\Type\DateTime;
use \Jamilco\Main\Manzana;

class Coupon
{
    const DISCOUNT_XML_ID = 'coupon_500';

    /**
     * агент, рассылающий письма о том, что осталось 3 дня до сгорания купона
     *
     * @return string
     */
    public static function checkCoupons()
    {
        Loader::IncludeModule('sale');

        $discountId = self::getDiscountID();
        $now = new DateTime();
        $twoDays = new DateTime();
        $twoDays->add('2 days');
        $threeDays = new DateTime();
        $threeDays->add('3 days');
        $dbCoupon = DiscountCouponTable::getList(
            array(
                'filter' => array(
                    'DISCOUNT_ID' => $discountId,
                    '>ACTIVE_TO'  => $now,          //
                    '>=ACTIVE_TO' => $twoDays,      // срок - от двух дней
                    '<=ACTIVE_TO' => $threeDays,    // срок - до трех дней
                )
            )
        );
        while ($arCoupon = $dbCoupon->Fetch()) {
            $email = self::getEmailFromDescription($arCoupon['DESCRIPTION']);
            \CEvent::Send(
                'COUPON_500_SOON',
                SITE_ID,
                array(
                    'EMAIL'   => $email,
                    'COUPON'  => $arCoupon['COUPON'],
                    'DATE_TO' => $arCoupon['ACTIVE_TO']->format('d.m.Y'),
                ),
                'N'
            );
            \CEvent::CheckEvents();
        }

        return '\Jamilco\Delivery\Coupon::checkCoupons();';
    }

    /**
     * генерирует купон и отправляет его на почту покупателю
     *
     * @param string $email
     */
    public static function sendCoupon($email = '')
    {
        $start = new DateTime();
        $end = new DateTime();

        if (method_exists('\Jamilco\Loyalty\Common', 'discountsAreMoved') && \Jamilco\Loyalty\Common::discountsAreMoved()) {
            // купон создается в Манзане
            $coupon = Manzana::getInstance()->generateCoupon('', 'coupon500');
        } else {
            $discountId = self::getDiscountID();
            $coupon = DiscountCouponTable::generateCoupon(true);
            $end->Add('1 month');
            DiscountCouponTable::add(
                array(
                    'DISCOUNT_ID' => $discountId,
                    'TYPE'        => DiscountCouponTable::TYPE_ONE_ORDER,
                    'ACTIVE'      => 'Y',
                    'ACTIVE_FROM' => $start,
                    'ACTIVE_TO'   => $end,
                    'DESCRIPTION' => 'Купон отправлен: '.$email,
                    'COUPON'      => $coupon,
                )
            );
        }

        \CEvent::Send(
            'SUBSCRIBE_COUPON',
            SITE_ID,
            array(
                'EMAIL'     => $email,
                'COUPON'    => $coupon,
                'DATE_FROM' => $start->format('d.m.Y'),
                'DATE_TO'   => $end->format('d.m.Y'),
            ),
            'N'
        );
        \CEvent::CheckEvents();
    }

    /**
     * возвращает ID правила (и создает его)
     *
     * @return mixed
     */
    public static function getDiscountID($xmlId = 'coupon_500')
    {
        if (!$xmlId) return false;

        $di = DiscountTable::getList(
            array(
                'filter' => array('XML_ID' => $xmlId),
                'limit'  => 1,
                'select' => array('ID'),
            )
        );
        if ($arDiscount = $di->Fetch()) {
            $discountId = $arDiscount['ID'];
        } else {
            $arFields = array(
                'LID'            => SITE_ID,
                'CURRENCY'       => 'RUB',
                'NAME'           => 'Купоны на 500 рублей',
                'XML_ID'         => $xmlId,
                'ACTIVE'         => 'Y',
                'SORT'           => '100',
                'PRIORITY'       => 1,
                'LAST_DISCOUNT'  => 'Y',
                'CONDITIONS'     => array(
                    'CLASS_ID' => 'CondGroup',
                    'DATA'     => array(
                        'All'  => 'AND',
                        'True' => 'True',
                    ),
                    'CHILDREN' => array(
                        array(
                            'CLASS_ID' => 'CondBsktAmtGroup',
                            'DATA'     => array(
                                'logic' => 'EqGr',
                                'Value' => 3000,
                                'All'   => 'AND',
                            ),
                            'CHILDREN' => array(),
                        ),
                    ),
                ),
                'ACTIONS'        => array(
                    'CLASS_ID' => 'CondGroup',
                    'DATA'     => array(
                        'All' => 'AND',
                    ),
                    'CHILDREN' => array(
                        array(
                            'CLASS_ID' => 'ActSaleBsktGrp',
                            'DATA'     => array(
                                'Type'  => 'Discount',
                                'Value' => 500,
                                'Unit'  => 'CurAll',
                                'All'   => 'AND',
                            ),
                        ),
                    ),
                ),
                'DISCOUNT_TYPE'  => 'V',
                'DISCOUNT_VALUE' => '0',
                'USE_COUPONS'    => 'Y',
                'USER_GROUPS'    => array(2),
            );

            $discountId = \CSaleDiscount::Add($arFields);
        }

        return $discountId;
    }

    public static function getEmailFromDescription($text = '')
    {
        $email = explode(':', $text);
        $email = array_pop($email);
        $email = trim($email);

        return $email;
    }
}