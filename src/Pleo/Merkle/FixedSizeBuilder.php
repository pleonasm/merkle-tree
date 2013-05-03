<?php
namespace Pleo\Merkle;

/**
 * Builds a merkle tree of a given width
 *
 * This is used to pre-build the total amount of nodes equal to the total
 * hashes that you would need to calculate a merkle tree without having the
 * data that is going to be hashed yet.
 *
 * For example: say you are downloading chunks of a file out of order. If you
 * know the length of the file, and decide on an appropriate chunk size, you
 * would know the merkle tree width ahead of time. You could construct the
 * tree and start adding data components one at a time. Any subtree that could
 * be hashed will be hashed as soon as possible and references to the
 * underlying nodes broken.
 */
class FixedSizeBuilder
{
    private $chunks;
    private $htree;
    private $treeRoot;
    private $finished;

    private static function buildHtreeRow($width, callable $hasher)
    {
        $row = [];
        if ($width === 1) {
            $row[] = new TwoChildrenNode($hasher);
            return $row;
        }

        $rowSize = ceil($width / 2);
        $odd = $width % 2;

        for ($i = 0; $i < $rowSize; $i++) {
            $row[] = new TwoChildrenNode($hasher);
        }

        if ($odd) {
            $row[$rowSize - 1] = new OneChildDuplicate($row[$rowSize - 1]);
        }

        $parents = buildHtreeRow($rowSize);
        $treeRoot = array_shift($parents);
        $pRowSize = count($parents);
        array_unshift($row, $treeRoot);

        if ($pRowSize === 0) {
            $row[0]->data($row[1], $row[2]);
            return $row;
        }

        $pOdd = count($parents) % 2;
        foreach ($parents as $i => $parent) {
            $index = ($i * 2) + 1;
            if ($i === $pRowSize && $pOdd) {
                $parent->data($row[$index]);
                continue;
            }
            $parent->data($row[$index], $row[$index + 1]);
        }

        return $row;
    }

    public function __construct($width, callable $hasher, callable $finished = null)
    {
        $this->chunks = array_fill(0, $width, null);
        $this->finished = $finished;
        $this->htree = self::buildHtree($width, $hasher);
        $this->treeRoot = array_shift($this->htree);
    }
}
