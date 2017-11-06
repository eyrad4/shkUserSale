# Использование класса evoSale

Данный класс работает строго с определенным каталогом акционных предложений, если вам нужно что-то изменить меняйте на свой страх и риск.

Установка немного сложновата

Создаем такие ТВ параметры:

 * action_item_count - тип number, значение по умолчанию 2
 * action_procent_sales - тип number, значение по умолчанию 20
 * action_variant - тип чекбокс Да==1

Каталог с акционными акциями должен выглядеть так:
* Акция, в нем тв параметр в котором список артикулов через запятую, пример "100000,900009,АААА509"

Плагин:
* Создаем плагин с любым именем
* Событие OnSHKcalcTotalPrice

Код плагина

```
$e = &$modx->Event;

switch($e->name){
case 'OnSHKcalcTotalPrice':
	if(!empty($_SESSION['purchases'])){
	
		$totalFullPrice = $e->params['totalPrice'];
	
		require_once($_SERVER['DOCUMENT_ROOT'].'/assets/plugins/userSale/userSale.php');		
		
		$sale = new userSale($modx, $purchases, 0, 164); //unserialize($_SESSION['purchases'])

		$totalSum = $sale->newPrice();	
		$totalSale = $totalFullPrice - $totalSum['totalPrice'];
		
		if($totalFullPrice != $totalSum['totalPrice']){
			$discount_action = '<div class="total-all total-sale-all">Додаткова знижка: <b>'.round($totalSale).'</b> грн </div>';	
			$modx->setPlaceholder('discount_action', $discount_action);	
		}	
	
		$e->output($totalSum['totalPrice']);
	
	}	
	
break;	

}
```
Снипет и его код, его помещаем в чанк полной корзины снипета Shopkeeper &cartRowTpl=`shopCartRowTpl`
Он служит для вывода информации для юзера

```
<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/assets/plugins/userSale/userSale.php');

$sale = new userSale($modx, unserialize($_SESSION['purchases']));

echo $sale->showPlaceholdersInItemCart($docid);
?>
```

В шаблон полной корзины добавляем placeholder [+[+discount_action+]+] он выводит скидку


