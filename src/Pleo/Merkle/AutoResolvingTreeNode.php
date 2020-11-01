<?php
/**
 * @copyright ©2020 Matthew Nagi
 * @license http://opensource.org/licenses/BSD-2-Clause BSD 2-Clause License
 */

namespace Pleo\Merkle;

use RuntimeException;

/**
 * Represents a single node in a Merkle tree
 */
class AutoResolvingTreeNode
{
    /**
     * @var AutoResolvingTreeNode|null
     */
    private $parent;

    /**
     * @var AutoResolvingTreeNode|null
     */
    private $left;

    /**
     * @var AutoResolvingTreeNode|null
     */
    private $right;

    /**
     * @var string|null
     */
    private $data;

    public function __construct()
    {
      $this->parent = null;
      $this->left = null;
      $this->right = null;
      $this->data = null;
    }

    public function getParent()
    {
        return $this->parent;
    }

    private function setParent(AutoResolvingTreeNode $parent)
    {
        $this->parent = $parent;
    }

    public function setLeft(AutoResolvingTreeNode $node)
    {
        $this->left = $node;
        $node->setParent($this);
    }

    public function setRight(AutoResolvingTreeNode $node)
    {
        $this->right = $node;
        $node->setParent($this);
    }

    public function setData(string $data)
    {
        if (is_string($this->data)) {
            throw new RuntimeException('Data can only be set to a node once');
        }

        $this->data = $data;
        if (!$this->parent) {
            return true;
        }
    }

    /**
     * Returns data in the node
     *
     * @return string|null Returns the data as a string or null if ->setData() has not been called yet.
     */
    public function getData()
    {
        return $this->data;
    }
}
