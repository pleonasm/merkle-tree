<?php
namespace Pleo\Merkle;

use InvalidArgumentException;
use LogicException;
use UnexpectedValueException;

class TreeNode
{
    const ERR_SETTYPE = '->data() can only be passed two strings or two instances of TreeNode';

    /**
     * @var callable|null
     */
    private $hasher;

    /**
     * @var string|TreeNode|null|boolean
     */
    private $first;

    /**
     * @var string|TreeNode|null|boolean
     */
    private $second;

    /**
     * @var string|null
     */
    private $hash;

    /**
     * @param callable $hasher
     */
    public function __construct(callable $hasher)
    {
        $this->hasher = $hasher;
    }

    /**
     * @return string|null
     */
    public function hash()
    {
        if ($this->hash) {
            return $this->hash;
        }

        if (is_null($this->first) && is_null($this->second)) {
            return null;
        }

        $first = &$this->first;
        $second = &$this->second;
        if ($this->first instanceof TreeNode) {
            $first = $this->first->hash();
            $second = $this->second->hash();
            if ($first === null || $second === null) {
                return null;
            }
        }

        $hash = call_user_func($this->hasher, $first . $second);

        $this->hash = $hash;
        $this->first = false;
        $this->second = false;
        return $this->hash;
   }

    /**
     * @param string|TreeNode $first
     * @param string|TreeNode $second
     * @throws LogicException
     * @throws InvalidArgumentException
     * @return null
     */
    public function data($first, $second)
    {
        if ($this->first && $this->second || $this->first === false && $this->second === false) {
            throw new LogicException('You cannot set data twice');
        }

        if (
            is_string($first) && is_string($second) ||
            $first instanceof TreeNode && $second instanceof TreeNode
        ) {
            $this->first = $first;
            $this->second = $second;
            return;
        }

        throw new InvalidArgumentException(self::ERR_SETTYPE);
    }
}
