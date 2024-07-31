<?php
declare(strict_types=1);

namespace SamuelbSource\EncryptionAddons\Model\Config;

use Magento\Framework\Config\ScopeInterface;
use Magento\Config\Model\Config\Structure\Data;
use Magento\Config\Model\Config\Structure\Element\FlyweightFactory;
use Magento\Config\Model\Config\Structure\Element\Iterator\Tab;
use Magento\Config\Model\Config\ScopeDefiner;

/**
 * Custom Config Structure class for reloading configuration data with a specific scope
 */
class Structure extends \Magento\Config\Model\Config\Structure
{
    private ScopeInterface $configScope;
    private Data $structureData;

    /**
     * Constructor
     *
     * @param ScopeInterface $configScope
     * @param Data $structureData
     * @param Tab $tabIterator
     * @param FlyweightFactory $flyweightFactory
     * @param ScopeDefiner $scopeDefiner
     */
    public function __construct(
        ScopeInterface $configScope,
        Data $structureData,
        Tab $tabIterator,
        FlyweightFactory $flyweightFactory,
        ScopeDefiner $scopeDefiner
    ) {
        $this->configScope = $configScope;
        $this->structureData = $structureData;
        parent::__construct($structureData, $tabIterator, $flyweightFactory, $scopeDefiner);
    }

    /**
     * Reload configuration data
     *
     * @param string|null $scope Optional scope to reload data for a specific scope
     * @return self
     */
    public function reloadData($scope = null)
    {
        if ($scope !== null) {
            $currentScope = $this->configScope->getCurrentScope();
            $this->configScope->setCurrentScope($scope);
        }

        // Reload structure data
        $this->_data = $this->structureData->get();

        if ($scope !== null) {
            $this->configScope->setCurrentScope($currentScope);
        }

        return $this;
    }
}
