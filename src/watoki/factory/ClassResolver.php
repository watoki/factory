<?php
namespace watoki\factory;
 
use PhpParser\Error;
use PhpParser\Lexer;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\Parser;

class ClassResolver {

    static private $cache = array();

    private $context;

    public function __construct(\ReflectionClass $context) {
        $this->context = $context;
    }

    public function resolve($class) {
        if ($this->exists($class)) {
            return $class;
        }

        $prependedClass = $this->prependWithCurrentNamespace($class);
        if ($this->exists($prependedClass)) {
            return $prependedClass;
        }

        return $this->findAliasedClass($class);
    }

    public function exists($class) {
        return class_exists($class) || interface_exists($class);
    }

    private function prependWithCurrentNamespace($class) {
        return $this->context->getNamespaceName() . '\\' . $class;
    }

    private function findAliasedClass($class) {
        $stmts = $this->parse();

        foreach ($stmts as $stmt) {
            if ($stmt instanceof Namespace_) {
                $stmts = $stmt->stmts;
                break;
            }
        }

        foreach ($stmts as $stmt) {
            if ($stmt instanceof Use_) {
                foreach ($stmt->uses as $use) {
                    if ($use instanceof UseUse && $use->alias == $class) {
                        return $use->name->toString();
                    }
                }
            }
        }

        return null;
    }

    private function parse() {
        if (!file_exists($this->context->getFileName())) {
            return array();
        }

        $contextName = $this->context->getName();
        if (!array_key_exists($contextName, self::$cache)) {
            try {
                $parser = new Parser(new Lexer());
                self::$cache[$contextName] = $parser->parse(file_get_contents($this->context->getFileName()));
            } catch (Error $e) {
                throw new \Exception("Error while parsing [{$this->context->getName()}]: " . $e->getMessage());
            }
        }

        return self::$cache[$contextName];
    }

}
