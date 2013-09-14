<?php
namespace spec\watoki\factory\injector;

use watoki\factory\Factory;
use watoki\factory\Injector;
use watoki\scrut\Fixture;
use watoki\scrut\Specification;

class InjectorFixture extends Fixture {

    static $loaded = array();

    private $instance;

    private $className;

    public function __construct(Specification $spec, Factory $factory) {
        parent::__construct($spec, $factory);
        self::$loaded = array();
    }

    public function givenTheClass_InTheNamespace($className, $namespace) {
        $this->givenTheClassDefinition_OfTheClass("namespace $namespace; class $className {}", $namespace . '\\' . $className);
    }

    public function givenTheClass_WithTheDocComment($className, $docComment) {
        $this->givenTheClassDefinition_OfTheClass("
            $docComment
            class $className {}
        ", $className);
    }

    public function givenTheClass_InTheNameSpace_WithTheDocComment($className, $nameSpace, $docComment) {
        $this->givenTheClassDefinition_OfTheClass("
            namespace $nameSpace;

            $docComment
            class $className {}
        ", $nameSpace . '\\' . $className);
    }

    public function givenTheClassDefinition_OfTheClass($definition, $className) {
        $dir = __DIR__ . '/tmp';
        $file = $dir . '/' . basename($className) . '.php';
        @mkdir($dir);
        file_put_contents($file, "<?php {$definition}");
        /** @noinspection PhpIncludeInspection */
        include $file;

        $this->spec->undos[] = function () use ($dir, $file) {
            @unlink($file);
            @rmdir($dir);
        };

        $this->className = $className;
    }

    public function whenIInjectTheProperties() {
        $className = $this->className;
        $this->instance = new $className;

        $injector = new Injector($this->spec->factory);
        $injector->injectPropertyAnnotations($this->instance);
    }

    public function thenThereShouldBeAProperty_WithAnInstanceOf($propertyName, $class) {
        $this->spec->assertInstanceOf($class, $this->instance->$propertyName);
    }

    public function theTheProperty_ShouldBeAnInstanceOf($propertyName, $class) {
        $reflection = new \ReflectionProperty(get_class($this->instance), $propertyName);
        $reflection->setAccessible(true);
        $this->spec->assertInstanceOf($class, $reflection->getValue($this->instance));
    }

    public function thenTheShouldBeNoProperty($propertyName) {
        $this->spec->assertFalse(isset($this->instance->$propertyName));
    }

    public function thenTheLoadedDependency_ShouldBe($int, $class) {
        $this->spec->assertEquals($class, self::$loaded[$int - 1]);
    }

}