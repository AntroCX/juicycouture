<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Способы оплаты");
?><div class="payment-methods">
	<h1 class="payment-methods__title">СПОСОБЫ ОПЛАТЫ</h1>
	<div class="payment-methods__desk">
		<p>
			Вы можете оплатить Заказ на сайте Juicy Couture одним из двух доступных способов:
		</p>
		<p>
			Оплата банковской картой онлайн;
		</p>
		<p>
			Оплата наличными при получении или используя банковскую карту.
		</p>
		<p>
			*Оплата наличными курьеру или используя банковскую карту при получении заказа не доступна при заказе в регионы РФ, за исключением Москвы, МО, СПБ, ЛО.⁠
		</p>
	</div>
	<div class="payment-methods__content">
		<h3 class="payment-methods__method">Оплата банковской картой онлайн</h3>
		<p class="payment-methods__text">
			К оплате принимаются карты любых банков VISA, VISA Electron, VISA International, MasterCard Worldwide, Maestro, МИР, JCB.
		</p>
		<div class="payment-methods__cards">
			<img alt="Карта банка - visa" src="/local/templates/main/assets/img/payment-methods/visa.svg"><img alt="Карта банка - master-card" src="/local/templates/main/assets/img/payment-methods/master-card.svg"><img alt="Карта банка - mir" src="/local/templates/main/assets/img/payment-methods/mir.svg">
		</div>
	</div>
	<div class="payment-methods__content">
		<h3 class="payment-methods__method">Описание процесса передачи данных</h3>
		<p class="payment-methods__text">
			Для оплаты (ввода реквизитов Вашей карты) Вы будете перенаправлены на платёжный шлюз ПАО СБЕРБАНК. Соединение с платёжным шлюзом и передача информации осуществляется в защищённом режиме с использованием протокола шифрования SSL. В случае если Ваш банк поддерживает технологию безопасного проведения интернет-платежей Verified By Visa, MasterCard SecureCode, MIR Accept, J-Secure для проведения платежа также может потребоваться ввод специального пароля.
		</p>
		<p class="payment-methods__text">
			Настоящий сайт поддерживает 256-битное шифрование. Конфиденциальность сообщаемой персональной информации обеспечивается ПАО СБЕРБАНК. Введённая информация не будет предоставлена третьим лицам за исключением случаев, предусмотренных законодательством РФ. Проведение платежей по банковским картам осуществляется в строгом соответствии с требованиями платёжных систем МИР, Visa Int., MasterCard Europe Sprl, JCB.
		</p>
	</div>
	<div class="payment-methods__content">
		<h3 class="payment-methods__method">Оплата наличными при получении</h3>
		<p class="payment-methods__text">
			Вы можете оплатить свой Заказ наличными непосредственно при получении.
		</p>
	</div>
</div><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>