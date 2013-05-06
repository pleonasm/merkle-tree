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
    private $width;
    private $hasher;
    private $htree;
    private $treeRoot;
    private $finished;

    private static function buildHtreeRow($width, callable $hasher)
    {
        $row = [];
        if ($width === 2) {
            $row[] = new TwoChildrenNode($hasher);
            return $row;
        }

        if ($width === 3) {
            $row[] = new TwoChildrenNode($hasher);
            $row[] = new TwoChildrenNode($hasher);
            $row[] = new OneChildDuplicateNode(new TwoChildrenNode($hasher));
            $row[0]->data($row[1], $row[2]);
            return $row;
        }

        $rowSize = (int) ceil($width / 2);
        $odd = $width % 2;

        for ($i = 0; $i < $rowSize; $i++) {
            $row[] = new TwoChildrenNode($hasher);
        }

        if ($odd) {
            $row[$rowSize - 1] = new OneChildDuplicateNode($row[$rowSize - 1]);
        }

        $parents = self::buildHtreeRow($rowSize, $hasher);
        $treeRoot = array_shift($parents);
        $pRowSize = count($parents);
        array_unshift($row, $treeRoot);

        if ($pRowSize === 0) {
            $row[0]->data($row[1], $row[2]);
            return $row;
        }

        $pOdd = $rowSize % 2;
        foreach ($parents as $i => $parent) {
            $index = ($i * 2) + 1;
            if ($i + 1 === $pRowSize && $pOdd) {
                $parent->data($row[$index]);
                continue;
            }
            $parent->data($row[$index], $row[$index + 1]);
        }

        return $row;
    }

    public function __construct($width, callable $hasher, callable $finished = null)
    {
        $this->width = $width;
        $this->chunks = array_fill(0, $width, null);
        $this->hasher = $hasher;
        $this->finished = $finished;
        $this->htree = self::buildHtreeRow($width, $hasher);
        $this->treeRoot = array_shift($this->htree);

        if (count($this->htree) === 0) {
            $this->htree[0] = $this->treeRoot;
        }
    }

    public function hash()
    {
        return $this->treeRoot->hash();
    }

    public function set($i, $v)
    {
        $this->chunks[$i] = $v;
        $odd = $i % 2;
        if ($odd) {
            $i--;
        }

        if ($i + 1 === $this->width) {
            $data = call_user_func($this->hasher, $this->chunks[$i]);
            $idx = (int) ($i / 2);
            $this->htree[$idx]->data($data);
            $this->treeRoot->hash();
            unset($this->chunks[$i]);
            return;
        }

        if (isset($this->chunks[$i]) && isset($this->chunks[$i + 1])) {
            $first = call_user_func($this->hasher, $this->chunks[$i]);
            $second = call_user_func($this->hasher, $this->chunks[$i + 1]);
            $idx = (int) ($i / 2);
            $this->htree[$idx]->data($first, $second);
            $this->treeRoot->hash();
            unset($this->chunks[$i]);
            unset($this->chunks[$i + 1]);
        }
    }
}
