<?php

//Добавил в гит

class userSale{

    public $product_id = 0;
    public $purchases_array = array();
    public $new_price_total = 0;
    public $tv_id_action = 164;
    protected $modx = null;


    public function __construct(\DocumentParser $modx, $purchases_array, $new_price_total = 0, $tv_id_action = 164)
    {
        $this->modx = $modx;
        // $this->product_id = $product_id;
        $this->purchases_array = $purchases_array;
        $this->new_price_total = $new_price_total;
        $this->tv_id_action = $tv_id_action;

    }

    //получаем id страницы в которой проходит акция
    public function findActionFolderId($product_id){

        $product_article = $this->modx->runSnippet('DocInfo', array('docid' => $product_id, 'field' => 'longtitle'));

        //ищем этот артикул в каталоге акций
        $result = $this->modx->db->getValue($this->modx->db->query( 'SELECT content.id FROM '.$this->modx->getFullTableName('site_content').' as content
				LEFT JOIN '.$this->modx->getFullTableName('site_tmplvar_contentvalues').' as tv ON ( content.id = tv.contentid and tv.tmplvarid = 127  )
				LEFT JOIN '.$this->modx->getFullTableName('site_tmplvar_contentvalues').' as variant ON ( content.id = variant.contentid and variant.tmplvarid = '.$this->tv_id_action.'  )

				WHERE
				content.template = 20
				AND
				content.published = 1
				AND
				content.parent = 169970
				AND
				variant.value = 1
				AND
				tv.value LIKE "%'.$product_article.'%"
				LIMIT 1
				'));

        return $result;
    }
	
	//получаем с тв параметра документа размер скидки 
    public function getValuePricePercent(){
        //action_procent_sales строгое имя тв параметра
        $action_folder_id = $this->findActionFolderId($item[0]);
        
        return $this->modx->runSnippet('DocInfo', array('docid' => $action_folder_id, 'field' => 'action_procent_sales'));  
    }

    //получаем с тв параметра количество товар от которых начисняляем скидку
    public function getValueCountSale(){
        //action_item_count строгое имя тв параметра
        $action_folder_id = $this->findActionFolderId($item[0]);
        
        return $this->modx->runSnippet('DocInfo', array('docid' => $action_folder_id, 'field' => 'action_item_count'));  
    }

    //получаем списомк всех артикулов акции и заносим их в массив
    public function getAllActionArticles($folder_id){

        $action_list_article = $this->modx->runSnippet('DocInfo', array('docid' => $folder_id, 'field' => 'action_article'));

        $action_list_article_array = explode(',', $action_list_article);

        return $action_list_article_array;


    }

    //получаем артикул товара
    public function getProductArticle($product_id){

        $product_article = $this->modx->runSnippet('DocInfo', array('docid' => $product_id, 'field' => 'longtitle'));
        return $product_article;

    }

    //считаем количество акционных товаров и записываеи их в массив
    public function productDiscountCount(){


        foreach ($this->purchases_array as $item) {

            $action_folder_id = $this->findActionFolderId($item[0]);

            if($action_folder_id){

                $action_list_array = $this->getAllActionArticles($action_folder_id);

                if(in_array($this->getProductArticle($item[0]), $action_list_array )){

                    $data['productCount'] += $item[1];
                    $data['actionFolderId'] += $item[1];
                    $data['changePricesIds'][] = $item[0];                   

                }else{

					$data['productCount'] += 0;
					$data['actionFolderId'] += 0;
					$data['changePricesIds'][] = $item[0];

            }

            }

        }
		 		return $data;

    }


    public function registerUserDiscount(){

        $userData = $this->modx->getWebUserInfo($this->modx->getLoginUserID());

        if(!empty($userData['comment'])){

            $json = json_decode($userData['comment'], true); //{"sale":"5","discountcard":"123456"}

            if (is_numeric($json['sale'])) {

                $discount = $json['sale']; //discount 5%

                return $discount;

            }

        }


    }


    //считаем цену для юзеров у которых есть дисконты, скидка действует только на товары у которых цена=акционная цена
    public function newPriceUserDiscount($product_id, $product_price, $product_count){

        //скидка для зарегистрированных пользователей с дисконтом

        $old_price =  $this->modx->runSnippet('DocInfo', array('docid' => $product_id, 'field' => 'oldPrice'));


        if(isset($product_price) AND empty($old_price) OR ($product_price == $old_price)){

            $user_discount = $this->registerUserDiscount();

            if($user_discount){

                $price = ($product_price * $product_count) * (1-$user_discount/100);

            }else{

                $price =  $product_price * $product_count;
            }

            /*
            if($this->modx->getLoginUserID()){

                $userData = $this->modx->getWebUserInfo($this->modx->getLoginUserID());

                if(!empty($userData['comment'])){

                    $json = json_decode($userData['comment'], true); //{"sale":"5","discountcard":"123456"}

                    if (is_numeric($json['sale'])) {

                        $discount = $json['sale']; //discount 5%

                        $price = ($product_price * $product_count) * (1-$discount/100);

                    }

                }

            }else{

                $price =  $product_price * $product_count;

            }
            */
        }else{

            $price =  $product_price * $product_count;
        }

        return $price;
    }

    //считаем цены для акционных товаров
    public function newPriceAction($product_id, $product_price, $product_count, $counts){

        $is_action_product = $this->findActionFolderId($product_id);
		
		$action_variant =  $this->modx->runSnippet('DocInfo', array('docid' => $is_action_product, 'field' => 'action_variant'));

        if(!empty($is_action_product) AND $action_variant == 1){
            if($counts < $this->getValueCountSale()){

                $price = $product_price * $product_count;

            }else{

                //получаем цену oldPrice, от нее мы будем делать скидку
                $old_price =  $this->modx->runSnippet('DocInfo', array('docid' => $product_id, 'field' => 'oldPrice'));

				
                $discount = ( $old_price * $product_count) / 100 * $this->getValuePricePercent();
                $price = round( $old_price * $product_count) - $discount;


            }

        }else{

            $price =  $product_price * $product_count;

        }

        return $price;

    }


    public function newPrice(){

        $array = $this->productDiscountCount();

		//echo '<pre>';
		//print_r($array);
		//die();
        foreach ($this->purchases_array  as $key => $item) {

			if(!empty($array['changePricesIds'])){
				if(in_array($item[0],  $array['changePricesIds']) ){

					$new_price = $this->newPriceAction($item[0],$item[2],$item[1], $array['productCount']);

				}else{

					$new_price = $this->newPriceUserDiscount($item[0],$item[2],$item[1]);

				}
			}else{
			
				$new_price = $this->newPriceUserDiscount($item[0],$item[2],$item[1]);
			
			}

            //$new_price_total += $new_price;
            $new_price_total += $new_price; //$item[2]*$item[1];

        }



        $data['totalPrice'] = $new_price_total;

        return $data;

    }

    function showPlaceholdersInItemCart($product_id){

                $action_folder_id = $this->findActionFolderId($product_id);
                $array = $this->productDiscountCount();

                if(!empty($action_folder_id)){

                    if($array['productCount'] < $this->getValueCountSale()){

                        return '<div class="discount-help">Додайте ще один товар з данної <a href="'.$this->modx->makeUrl($action_folder_id).'">акції</a> щоб отримати додаткову знижку!</div>';

                    }else{

                        return '<div class="discount-help">Додаткова знижка за участь в акції отримана!</div>';

                    }

                }

                $product_price =  $this->modx->runSnippet('DocInfo', array('docid' => $product_id, 'field' => 'price'));
                $old_price =  $this->modx->runSnippet('DocInfo', array('docid' => $product_id, 'field' => 'oldPrice'));
        
                if(isset($product_price) AND empty($old_price) OR ($product_price == $old_price)) {

                    $user_discount = $this->registerUserDiscount();
                    if ($user_discount) {

                        return '<div class="discount-help">Ви отримали додаткову знижку ' . $user_discount . ' % на товар як учасник дисконтної програми!</div>';

                    }
                }    


    }



    public function getModx()
    {
        return $this->modx;
    }

}
