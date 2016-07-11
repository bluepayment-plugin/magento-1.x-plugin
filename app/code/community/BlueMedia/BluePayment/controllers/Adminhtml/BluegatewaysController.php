<?php

class BlueMedia_BluePayment_Adminhtml_BluegatewaysController extends Mage_Adminhtml_Controller_Action {

    protected function _initAction() {
        $this->loadLayout()->_setActiveMenu("bluepayment/bluegateways")->_addBreadcrumb(Mage::helper("adminhtml")->__("Bluegateways  Manager"), Mage::helper("adminhtml")->__("Bluegateways Manager"));
        return $this;
    }

    public function indexAction() {
        $this->_title($this->__("BluePayment"));
        $this->_title($this->__("Manager Bluegateways"));

        $this->_initAction();
        $this->renderLayout();
    }

    public function syncAction() {
        $session = Mage::getSingleton('core/session');
        $result = Mage::helper('bluepayment/gateways')->syncGateways();

        if (isset($result['error'])){
            $session->addError($result['error']);
        }else{
            $session->addSuccess('Gateway list has been synchronized!');
            
        }
        $this->_redirect('admin_bluepayment/adminhtml_bluegateways/index');
        return;
    }

    public function editAction() {
        $this->_title($this->__("BluePayment"));
        $this->_title($this->__("Bluegateways"));
        $this->_title($this->__("Edit Item"));

        $id = $this->getRequest()->getParam("id");
        $model = Mage::getModel("bluepayment/bluegateways")->load($id);
        if ($model->getId()) {
            Mage::register("bluegateways_data", $model);
            $this->loadLayout();
            $this->_setActiveMenu("bluepayment/bluegateways");
            $this->_addBreadcrumb(Mage::helper("adminhtml")->__("Bluegateways Manager"), Mage::helper("adminhtml")->__("Bluegateways Manager"));
            $this->_addBreadcrumb(Mage::helper("adminhtml")->__("Bluegateways Description"), Mage::helper("adminhtml")->__("Bluegateways Description"));
            $this->getLayout()->getBlock("head")->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock("bluepayment/adminhtml_bluegateways_edit"))->_addLeft($this->getLayout()->createBlock("bluepayment/adminhtml_bluegateways_edit_tabs"));
            $this->renderLayout();
        } else {
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper("bluepayment")->__("Item does not exist."));
            $this->_redirect("*/*/");
        }
    }

    public function newAction() {

        $this->_title($this->__("BluePayment"));
        $this->_title($this->__("Bluegateways"));
        $this->_title($this->__("New Item"));

        $id = $this->getRequest()->getParam("id");
        $model = Mage::getModel("bluepayment/bluegateways")->load($id);

        $data = Mage::getSingleton("adminhtml/session")->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        Mage::register("bluegateways_data", $model);

        $this->loadLayout();
        $this->_setActiveMenu("bluepayment/bluegateways");

        $this->getLayout()->getBlock("head")->setCanLoadExtJs(true);

        $this->_addBreadcrumb(Mage::helper("adminhtml")->__("Bluegateways Manager"), Mage::helper("adminhtml")->__("Bluegateways Manager"));
        $this->_addBreadcrumb(Mage::helper("adminhtml")->__("Bluegateways Description"), Mage::helper("adminhtml")->__("Bluegateways Description"));


        $this->_addContent($this->getLayout()->createBlock("bluepayment/adminhtml_bluegateways_edit"))->_addLeft($this->getLayout()->createBlock("bluepayment/adminhtml_bluegateways_edit_tabs"));

        $this->renderLayout();
    }

    public function saveAction() {

        $post_data = $this->getRequest()->getPost();


        if ($post_data) {

            try {


                //save image
                try {

                    if ((bool) $post_data['gateway_logo_path']['delete'] == 1) {

                        $post_data['gateway_logo_path'] = '';
                    } else {

                        unset($post_data['gateway_logo_path']);

                        if (isset($_FILES)) {

                            if ($_FILES['gateway_logo_path']['name']) {

                                if ($this->getRequest()->getParam("id")) {
                                    $model = Mage::getModel("bluepayment/bluegateways")->load($this->getRequest()->getParam("id"));
                                    if ($model->getData('gateway_logo_path')) {
                                        $io = new Varien_Io_File();
                                        $io->rm(Mage::getBaseDir('media') . DS . implode(DS, explode('/', $model->getData('gateway_logo_path'))));
                                    }
                                }
                                $path = Mage::getBaseDir('media') . DS . 'bluepayment' . DS . 'bluegateways' . DS;
                                $uploader = new Varien_File_Uploader('gateway_logo_path');
                                $uploader->setAllowedExtensions(array('jpg', 'png', 'gif'));
                                $uploader->setAllowRenameFiles(false);
                                $uploader->setFilesDispersion(false);
                                $destFile = $path . $_FILES['gateway_logo_path']['name'];
                                $filename = $uploader->getNewFileName($destFile);
                                $uploader->save($path, $filename);

                                $post_data['gateway_logo_path'] = 'bluepayment/bluegateways/' . $filename;
                            }
                        }
                    }
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                    return;
                }
//save image


                $model = Mage::getModel("bluepayment/bluegateways")
                        ->addData($post_data)
                        ->setId($this->getRequest()->getParam("id"))
                        ->save();

                Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Bluegateways was successfully saved"));
                Mage::getSingleton("adminhtml/session")->setBluegatewaysData(false);

                if ($this->getRequest()->getParam("back")) {
                    $this->_redirect("*/*/edit", array("id" => $model->getId()));
                    return;
                }
                $this->_redirect("*/*/");
                return;
            } catch (Exception $e) {
                Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
                Mage::getSingleton("adminhtml/session")->setBluegatewaysData($this->getRequest()->getPost());
                $this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id")));
                return;
            }
        }
        $this->_redirect("*/*/");
    }

    public function deleteAction() {
        if ($this->getRequest()->getParam("id") > 0) {
            try {
                $model = Mage::getModel("bluepayment/bluegateways");
                $model->setId($this->getRequest()->getParam("id"))->delete();
                Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Item was successfully deleted"));
                $this->_redirect("*/*/");
            } catch (Exception $e) {
                Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
                $this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id")));
            }
        }
        $this->_redirect("*/*/");
    }

}
