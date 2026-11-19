<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Dev\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<Node\Stmt\Class_> */
class UnitTestFQNShouldMatchCoveredClassFQN implements Rule {
    private const PHPUNIT_TEST_CASE = 'PHPUnit\Framework\TestCase';

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function getNodeType(): string {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array {
        if (($className = $node->namespacedName->toString()) === null) {
            return [];
        }

        if ($this->reflectionProvider->hasClass($className) === false
            || $this->reflectionProvider->hasClass(self::PHPUNIT_TEST_CASE) === false) {
            return [];
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        $testCaseReflection = $this->reflectionProvider->getClass(self::PHPUNIT_TEST_CASE);
        if ($classReflection->isSubclassOfClass($testCaseReflection) === false) {
            return [];
        }

        $coversClasses = [];
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if ($attribute->name->toString() !== 'PHPUnit\Framework\Attributes\CoversClass') {
                    continue;
                }

                if (count($attribute->args) !== 1 || array_key_exists(0, $attribute->args) === false) {
                    continue;
                }

                if ($attribute->args[0]->value instanceof Node\Expr\ClassConstFetch === false
                    || $attribute->args[0]->value->class instanceof Node\Name === false) {
                    continue;
                }

                $coversClasses[] = $attribute->args[0]->value->class->toString();
            }
        }

        if (count($coversClasses) !== 1) {
            return [];
        }

        if (preg_match('/^(?<rootNamespace>.+)(?<testSuiteNamespace>\\\\test(?:s)?(?:\\\\unit)?)(?<testNamespace>.+)$/i', $className, $matches) !== 1) {
            return [];
        }

        $expectedTestFQN = str_replace($matches['rootNamespace'], $matches['rootNamespace'] . $matches['testSuiteNamespace'], $coversClasses[0]) . 'Test';
        if ($expectedTestFQN === $className) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Unit test FQN should match the namespace structure of the class it covers, expected %s but got %s',
                    $expectedTestFQN,
                    $className,
                ),
            )->line($node->getStartLine())
                ->identifier('unitTestFQN')
                ->build(),
        ];
    }
}
