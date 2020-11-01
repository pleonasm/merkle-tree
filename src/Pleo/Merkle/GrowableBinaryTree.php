<?php
/**
 * @copyright 2020 Matthew Nagi
 * @license http://opensource.org/licenses/BSD-2-Clause BSD 2-Clause License
 */
declare(strict_types=1);

namespace Pleo\Merkle;

use RangeException;

/**
 * Represents a single binary tree that can 'grow' from the bottom up
 *
 * Under the hood, this uses AutoResolvingTreeNode objects to represent each node of the tree. This tree is built from
 * the 'bottom-up'. You call addLeftNode() for each node you want on the bottom of the tree. For example, a tree with
 * two leaf nodes looks like this:
 *
 *     O
 *    / \
 *   O   O
 *
 * A tree with three leaf nodes:
 *
 *        O
 *      /   \
 *     O     O
 *    / \   /
 *   O   O O
 *
 * A tree with four leaf nodes:
 *
 *        O
 *      /   \
 *     O     O
 *    / \   / \
 *   O   O O   O
 *
 * A tree with five leaf nodes:
 *
 *            O
 *          /   \
 *         /     \
 *        O       O
 *      /   \      \
 *     O     O      O
 *    / \   / \   /
 *   O   O O   O O
 *
 * As the leaf nodes increase, the tree will grow "upward" in size and the root node WILL change.
 */
class GrowableBinaryTree
{
    /**
     * @var array<array<AutoResolvingTreeNode|null>>
     */
    private $grid;

    /**
     * @var AutoResolvingTreeNode|null
     */
    private $root;

    /**
     * @var callable
     */
    private $hasher;

    /**
     * @var boolean
     */
    private $locked;

    public function __construct(callable $hasher)
    {
        $this->grid = [[]];
        $this->root = null;
        $this->hasher = $hasher;
        $this->locked = false;
    }

    public function lock()
    {
        $this->locked = true;
        // TODO here we have to iterate over the rows and resolve any outstanding degenerate cases
    }

    /**
     * Adds a new leaf node to the binary tree
     *
     * By calling this, you *may* change which node is the tree's root node. You should always call root() after you
     * are done with all of your addLeafNode() calls otherwise you may have a reference to the wrong root node.
     *
     * @return AutoResolvingTreeNode Returns the new leaf node created in the tree
     */
    public function addLeafNode()
    {
        return $this->addNode(0);
    }

    /**
     * Returns a reference to the root node of the tree
     *
     * This object returned may change any time addBaseNode() is called. In fact it's better never to call this until
     * you are finished calling addBaseNode() as many times as you want.
     *
     * @return AutoResolvingTreeNode|null
     */
    public function root()
    {
        return $this->root;
    }

    /**
     * Returns the amount of levels of the tree.
     *
     * @return int
     */
    public function levels()
    {
        return count($this->grid);
    }

    /**
     * Adds a node to a given layer of the tree
     *
     * This is a recursive function. It will use at most one stack frame per the amount of levels of the tree plus one.
     * If you wish, you can ask for the amount of levels of the current tree by calling levels().
     *
     * @param int $row The "level" the new node should be placed at
     * @param AutoResolvingTreeNode|null $new The instance (if already created) to place into the tree
     * @return AutoResolvingTreeNode|null Returns the node instance if the $row argument is 0, otherwise returns null
     */
    private function addNode(int $row, AutoResolvingTreeNode $new = null)
    {
        $isBase = false;
        if ($row === 0) {
            $isBase = true;
        }

        if (!$new) {
            $new = new AutoResolvingTreeNode;
        }

        $this->grid[$row][] = $new;
        $size = count($this->grid[$row]);

        if ($size === 1)  {
            $this->root = $this->grid[$row][$size - 1];
            return $isBase ? $new : null;
        }

        if ($size === 2) {
            $newParent = new AutoResolvingTreeNode;
            $newParent->setLeft($this->grid[$row][0]);
            $newParent->setRight($this->grid[$row][1]);
            $this->grid[$row + 1][] = $newParent;
            $this->root = $newParent;
            return $isBase ? $new : null;
        }

        $odd = (bool) ($size % 2);

        if (!$odd) {
            $this->grid[$row][$size - 2]->getParent()->setRight($new);
            return $isBase ? $new : null;
        }

        $newParent = new AutoResolvingTreeNode;
        $newParent->setLeft($new);
        $this->addNode($row + 1, $newParent);
        return $isBase ? $new : null;
    }

    /**
     * @param int $i
     * @param string $v
     * @return string|null
     */
    public function set(int $i, string $v)
    {
        $size = count($this->grid[0]);
        $maxIndex = $size - 1;

        if ($i > $maxIndex) {
            throw new RangeException("Current max index is $maxIndex and you are trying to set $i");
        }

        if ($i < 0) {
            throw new RangeException("Index must be greater than 0");
        }

        return $this->setOnRow($i, $v, 0);
    }

    /**
     * Set a value on the 'tree grid' and
     *
     * @param int $i
     * @param string $v
     * @param int $row
     * @return string|null
     */
    private function setOnRow(int $i, string $v, int $row = 0)
    {
        $rowList = $this->grid[$row];
        $node = $rowList[$i];
        $node->setData($v);

        $size = count($rowList);
        $odd = (bool) ($size % 2);
        $oddIdx = (bool) ($i % 2);

        if (1 === $size) {
            return $v;
        }

        if ($odd && $i + 1 === $size) {
            if (!$this->locked) {
                return null;
            }

            // degenerate case handling (should be refactored)
            $newHash = call_user_func($this->hasher, $v . $v);
            $parentIndex = (int) ($i / 2);
            return $this->setOnRow($parentIndex, $newHash, $row + 1);
        }

        $siblingIdx = $i + ($oddIdx ? -1 : 1);
        $sibling = $rowList[$siblingIdx];
        if (null === $sibling->getData()) {
            return null;
        }

        $parentHashData = $oddIdx ? $sibling->getData() . $v : $v . $sibling->getData();
        $newHash = call_user_func($this->hasher, $parentHashData);
        $parentIndex = (int) ($i / 2);
        return $this->setOnRow($parentIndex, $newHash, $row + 1);
    }
}
