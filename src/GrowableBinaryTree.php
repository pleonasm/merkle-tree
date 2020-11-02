<?php
/**
 * @copyright 2020 Matthew Nagi
 * @license http://opensource.org/licenses/BSD-2-Clause BSD 2-Clause License
 */
declare(strict_types=1);

namespace Pleo\Merkle;

use RangeException;
use RuntimeException;

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
     * @return void
     */
    public function addLeafNode()
    {
        $this->addNode(0);
    }

    /**
     * Returns a reference to the root node of the tree
     *
     * This object returned may change any time addBaseNode() is called. In fact it's better never to call this until
     * you are finished calling addBaseNode() as many times as you want.
     *
     * @return string|false|null
     */
    public function root()
    {
        $rows = count($this->grid);
        return $this->grid[$rows - 1][0];
    }

    private function addNode(int $row)
    {
        $this->grid[$row][] = false;
        $size = count($this->grid[$row]);

        if ($size === 1) {
            return;
        }

        if ($size === 2) {
            $this->grid[$row + 1][] = false;
            return;
        }

        if ($size % 2 !== 0) {
            $this->addNode($row + 1);
        }
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

        if (is_string($this->grid[0][$i])) {
            throw new RuntimeException("Cannot set the same index twice");
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
        $rowList = &$this->grid[$row];
        $rowList[$i] = $v;

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
        if (false === $sibling) {
            return null;
        }

        $parentHashData = $oddIdx ? $sibling . $v : $v . $sibling;
        $newHash = call_user_func($this->hasher, $parentHashData);
        $parentIndex = (int) ($i / 2);
        return $this->setOnRow($parentIndex, $newHash, $row + 1);
    }
}
