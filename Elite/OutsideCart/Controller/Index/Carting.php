<?php

namespace Elite\OutsideCart\Controller\Index;



class Carting extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * Addpack constructor.
     * 
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
        $this->productFactory = $productFactory;

        parent::__construct($context);
    }
    public function execute()
    {
        try {

            $quote = $this->checkoutSession->getQuote();
            $params = array();
            /* Get product id from a URL like /outsidecart/index/carting?product=1_1,2_3.... */
            $product_str = $this->getRequest()->getParam('product');
            
            if(!isset($product_str)) 
            {
                $this->getResponse()->setRedirect('/');
            }
            $pIds = explode(',', $product_str);

             $allItems = $quote->getAllVisibleItems();
            foreach ($allItems as $item) {
                $itemId = $item->getItemId();
                $quote->removeItem($itemId);
            }
            
            foreach($pIds as $value) {
                $productId = explode('_', $value)[0];
                $curQty = explode('_', $value)[1];
                if($curQty>0){
                    $params = array();
                    $params['qty'] = $curQty;//product quantity
                    $product = $this->productFactory->create()->load($productId);
                    if ($product->getId()) {
                        $quote->addProduct(
                            $product,
                            intval($curQty)
                        );
                    }
                }
            }

            $this->cartRepository->save($quote);
            $this->checkoutSession->replaceQuote($quote)->unsLastRealOrderId();
            $this->messageManager->addSuccess(__('Add to cart successfully.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addException(
                $e,
                __('%1', $e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('error.'));
        }
        /*cart page*/
        $this->getResponse()->setRedirect('/checkout');
    }
}