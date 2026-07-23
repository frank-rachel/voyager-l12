<?php

namespace TCG\Voyager\Actions;

abstract class AbstractAction implements ActionInterface
{
    protected $dataType;
    protected $data;

    public function __construct($dataType, $data)
    {
        $this->dataType = $dataType;
        $this->data = $data;
    }

    public function getDataType()
    {
    }

    public function getPolicy()
    {
    }

    public function getRoute($key)
    {
        if (method_exists($this, $method = 'get'.ucfirst($key).'Route')) {
            return $this->$method();
        } else {
            return $this->getDefaultRoute();
        }
    }

    public function getAttributes()
    {
        return [];
    }

    public function convertAttributesToHtml()
    {
        $result = [];

        foreach ($this->getAttributes() as $key => $attribute) {
            $result[] = sprintf('%s="%s"', $key, $attribute);
        }

        return implode(" ", $result);
    }

    public function shouldActionDisplayOnDataType()
    {
        return $this->dataType->name === $this->getDataType() || $this->getDataType() === null;
    }

    public function shouldActionDisplayOnRow($row)
    {
        return true;
    }

    /**
     * Get parent context query params from the current request.
     */
    protected function getParentQueryParams(): array
    {
        $parentKeys = ['appparent', 'versionparent', 'questionparent', 'wfid', 'wfvid', 'olp', 'parent', 'closed', 'masterreport'];
        $params = [];
        foreach ($parentKeys as $key) {
            if (isset($_REQUEST[$key])) {
                $params[$key] = $_REQUEST[$key];
            }
        }
        return $params;
    }
}
