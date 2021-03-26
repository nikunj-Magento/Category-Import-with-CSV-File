<?php

namespace Itabtech\CategoryImport\Controller\Index;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class Index extends \Magento\Framework\App\Action\Action {

    protected $_pageFactory;
    protected $_categoryFactory;
    protected $_category;
    protected $_repository;
    protected $csv;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $pageFactory, \Magento\Catalog\Model\CategoryFactory $categoryFactory, \Magento\Catalog\Model\Category $category, \Magento\Framework\File\Csv $csv, \Magento\Catalog\Api\CategoryRepositoryInterface $repository) {
        $this->_pageFactory = $pageFactory;
        $this->_categoryFactory = $categoryFactory;
        $this->_category = $category;
        $this->_repository = $repository;
        $this->csv = $csv;
        return parent::__construct($context);
    }

    public function execute() {
        $post = $this->getRequest()->getFiles();
        if ($post) {
            if (isset($post['file_upload']['tmp_name'])) {
                $arrResult = $this->csv->getData($post['file_upload']['tmp_name']);
                foreach ($arrResult as $key => $value) {
                    if ($key > 0) {
// to skip the 1st row i.e title
                        $parentid = 2;
                        if (is_string($value[1])) {
                            $categoryTitle = $value[1]; // Category Name
                            $collection = $this->_categoryFactory->create()->getCollection()->addFieldToFilter('name', ['in' => $categoryTitle]);
                            if ($collection->getSize()) {
                                $parentid = $collection->getFirstItem()->getId();
                            }
                        } else if (is_int(($value[1])))
                            $parentid = $value[1];
//echo $parentid."<br>";//For reference
                        $data = ['data' => ["parent_id" => $parentid,
                                'name' => $value[2],
                                "is_active" => true,
                                "position" => 10,
                                "include_in_menu" => true,
                        ]];
                        $checkCategory = $this->_categoryFactory->create()->getCollection()->addFieldToFilter('name', ['in' => $value[2]])->addFieldToFilter('parent_id', ['in' => $parentid]);
                        if (!$checkCategory->getData()) {
                            $category = $this->_categoryFactory->create($data);
                            $result = $this->_repository->save($category);
                        }
                    }
                    $this->messageManager->addSuccessMessage('Category Imported Successfully');
                }
            }
        }
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }

}
