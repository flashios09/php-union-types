<?php
declare(strict_types=1);

use UnionTypes\Error\FatalError;
use UnionTypes\Error\TypeError;
use UnionTypes\Exception\ClassNotFoundException;
use UnionTypes\Exception\InvalidUnionTypeException;
use UnionTypes\Spec\Fixtures\Time;
use UnionTypes\UnionTypes;
use UnionTypes\Utilities\StackTrace;
use function Kahlan\allow;
use function Kahlan\beforeEach;
use function Kahlan\describe;
use function Kahlan\expect;
use function UnionTypes\Spec\getTrace;

describe("UnionTypes", function () {
    describe('::assertTypes(string ...$types): void', function () {
        beforeEach(function () {
            allow('debug_backtrace')
                ->toBeCalled()
                ->andReturn([getTrace('Math::add'), getTrace('UnionTypes::assertTypes')]);
        });

        describe("UnionTypes::assertTypes('NULL')", function () {
            it("should throw InvalidUnionTypeException `NULL`, use `null` instead", function () {
                $closure = function () {
                    UnionTypes::assertTypes('NULL');
                };

                expect($closure)->toThrow(new InvalidUnionTypeException('NULL', 'null'));
            });
        });

        describe("UnionTypes::assertTypes('integer')", function () {
            it("should throw InvalidUnionTypeException `integer`, use `int` instead", function () {
                $closure = function () {
                    UnionTypes::assertTypes('integer');
                };

                expect($closure)->toThrow(new InvalidUnionTypeException('integer', 'int'));
            });
        });

        describe("UnionTypes::assertTypes('double')", function () {
            it("should throw InvalidUnionTypeException `double`, use `int` instead", function () {
                $closure = function () {
                    UnionTypes::assertTypes('double');
                };

                expect($closure)->toThrow(new InvalidUnionTypeException('double', 'int'));
            });
        });

        describe("UnionTypes::assertTypes('decimal')", function () {
            it("should throw InvalidUnionTypeException `decimal`, use `float` instead", function () {
                $closure = function () {
                    UnionTypes::assertTypes('decimal');
                };

                expect($closure)->toThrow(new InvalidUnionTypeException('decimal', 'float'));
            });
        });

        describe("UnionTypes::assertTypes('boolean')", function () {
            it("should throw InvalidUnionTypeException `boolean`, use `bool` instead", function () {
                $closure = function () {
                    UnionTypes::assertTypes('boolean');
                };

                expect($closure)->toThrow(new InvalidUnionTypeException('boolean', 'bool'));
            });
        });

        describe("UnionTypes::assertTypes('object')", function () {
            $use = sprintf("{ClassName}::class or '%s' format", UnionTypes::CLASSNAME_PATTERN);
            it("should throw InvalidUnionTypeException `object`, use `$use` instead", function () use ($use) {
                $closure = function () {
                    UnionTypes::assertTypes('object');
                };
                expect($closure)->toThrow(new InvalidUnionTypeException('object', $use));
            });
        });

        describe("UnionTypes::assertTypes('MyNamespace\InexistentClassName')", function () {
            $inexistentClassName = 'MyNamespace\InexistentClassName';
            $it = function () use ($inexistentClassName) {
                $closure = function () use ($inexistentClassName) {
                    UnionTypes::assertTypes($inexistentClassName);
                };

                expect($closure)->toThrow(new ClassNotFoundException($inexistentClassName));
            };

            it("should throw ClassNotFoundException, `$inexistentClassName` not found", $it);
        });

        describe("UnionTypes::assertTypes('a non valid union type')", function () {
            $use = implode(
                ', ',
                array_map(function ($value) {
                    return "'" . $value . "'";
                }, UnionTypes::FULL_UNION_TYPES())
            );
            it("should throw InvalidUnionTypeException `a non valid union type`, use `$use` instead", function () {
                $closure = function () {
                    UnionTypes::assertTypes('a non valid union type');
                };

                expect($closure)
                    ->toThrow(new InvalidUnionTypeException(
                        'a non valid union type',
                        ...UnionTypes::FULL_UNION_TYPES()
                    ));
            });
        });

        describe(sprintf(
            "UnionTypes::assertTypes(%s)",
            "'int', 'float', 'string', 'bool', 'array', 'null', 'resource', 'stdClass', UnionTypes::class"
        ), function () {
            it("should pass", function () {
                $types = ['int', 'float', 'string', 'bool', 'array', 'null', 'resource', 'stdClass', UnionTypes::class];

                expect(UnionTypes::assertTypes(...$types))->toBe(null);
            });
        });
    });

    describe('::getType(mixed $value): string', function () {
        describe("UnionTypes::getType(1)", function () {
            it("should return 'int'", function () {
                expect(UnionTypes::getType(1))->toBe('int');
            });
        });

        describe("UnionTypes::getType(0)", function () {
            it("should return 'int'", function () {
                expect(UnionTypes::getType(0))->toBe('int');
            });
        });

        describe("UnionTypes::getType(1.2)", function () {
            it("should return 'float'", function () {
                expect(UnionTypes::getType(1.2))->toBe('float');
            });
        });

        describe("UnionTypes::getType('my string')", function () {
            it("should return 'string'", function () {
                expect(UnionTypes::getType('my string'))->toBe('string');
            });
        });

        describe("UnionTypes::getType(true)", function () {
            it("should return 'bool'", function () {
                expect(UnionTypes::getType(true))->toBe('bool');
            });
        });

        describe("UnionTypes::getType(false)", function () {
            it("should return 'bool'", function () {
                expect(UnionTypes::getType(false))->toBe('bool');
            });
        });

        describe("UnionTypes::getType([1, 2, 'k' => 'v'])", function () {
            it("should return 'array'", function () {
                expect(UnionTypes::getType([1, 2, 'k' => 'v']))->toBe('array');
            });
        });

        describe("UnionTypes::getType(null)", function () {
            it("should return 'null'", function () {
                expect(UnionTypes::getType(null))->toBe('null');
            });
        });

        describe("UnionTypes::getType(function () {})", function () {
            it("should return 'Closure'", function () {
                $closure = function () {
                    // ...
                };
                expect(UnionTypes::getType($closure))->toBe('Closure');
            });
        });

        describe("UnionTypes::getType(new \stdClass())", function () {
            it("should return 'stdClass'", function () {
                expect(UnionTypes::getType(new \stdClass()))->toBe('stdClass');
            });
        });

        describe("UnionTypes::getType(new UnionTypes())", function () {
            it("should return 'UnionTypes\UnionTypes'", function () {
                expect(UnionTypes::getType(new UnionTypes()))->toBe('UnionTypes\UnionTypes');
            });
        });

        describe("UnionTypes::getType(fopen('file.txt', 'r'))", function () {
            it("should return 'resource'", function () {
                $file = __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'file.txt';
                expect(UnionTypes::getType(fopen($file, 'r')))->toBe('resource');
            });
        });
    });

    describe('::stringify(mixed $value): string', function () {
        describe("UnionTypes::stringify(1)", function () {
            it("should return '1'", function () {
                expect(UnionTypes::stringify(1))->toBe('1');
            });
        });

        describe("UnionTypes::stringify(1.2)", function () {
            it("should return '1.2'", function () {
                expect(UnionTypes::stringify(1.2))->toBe('1.2');
            });
        });

        describe("UnionTypes::stringify(null)", function () {
            it("should return 'null'", function () {
                expect(UnionTypes::stringify(null))->toBe('null');
            });
        });

        describe("UnionTypes::stringify('my string')", function () {
            it("should return \"'my string'\" (wrapped with quotes)", function () {
                expect(UnionTypes::stringify('my string'))->toBe("'my string'");
            });
        });

        describe("UnionTypes::stringify(true)", function () {
            it("should return 'true'", function () {
                expect(UnionTypes::stringify(true))->toBe("true");
            });
        });

        describe("UnionTypes::stringify(false)", function () {
            it("should return 'false'", function () {
                expect(UnionTypes::stringify(false))->toBe("false");
            });
        });

        describe(
            "UnionTypes::stringify([1, 1.2, 'my string', true, null, new \stdClass(), function () {}, 'k' => 'v'])",
            function () {
                it("should return 'Array'", function () {
                    // phpcs:ignore Squiz.WhiteSpace.ScopeClosingBrace.ContentBefore
                    $array = [1, 1.2, 'my string', true, null, new \stdClass(), function () {}, 'k' => 'v'];
                    expect(UnionTypes::stringify($array))->toBe("Array");
                });
            }
        );

        describe("UnionTypes::stringify(function () {})", function () {
            it("should return 'object(Closure)'", function () {
                // phpcs:ignore Squiz.WhiteSpace.ScopeClosingBrace.ContentBefore
                expect(UnionTypes::stringify(function () {}))->toBe("object(Closure)");
            });
        });

        describe("UnionTypes::stringify(new \stdClass())", function () {
            it("should return 'object(stdClass)'", function () {
                expect(UnionTypes::stringify(new \stdClass()))->toBe("object(stdClass)");
            });
        });

        describe("UnionTypes::stringify(new UnionTypes())", function () {
            it("should return 'object(UnionTypes\UnionTypes)'", function () {
                expect(UnionTypes::stringify(new UnionTypes()))->toBe("object(UnionTypes\UnionTypes)");
            });
        });

        describe("UnionTypes::stringify(fopen('file.txt', 'r'))", function () {
            it("should return 'Resource id #{n}'", function () {
                $file = __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'file.txt';
                expect(UnionTypes::stringify(fopen($file, 'r')))->toMatch("/^Resource id #\d+$/");
            });
        });
    });

    describe('::assert($value, array $types): void', function () {
        describe("UnionTypes::assert(1.2, ['int', 'float'])", function () {
            it("should pass", function () {
                expect(UnionTypes::assert(1.2, ['int', 'float']))->toBe(null);
            });
        });

        describe("UnionTypes::assert('1.2', ['int', 'float'])", function () {
            $message = "`'1.2'` must be of the union type `int|float`, `string` given";
            it("should throw TypeError $message", function () use ($message) {
                allow('debug_backtrace')
                    ->toBeCalled()
                    ->andReturn([getTrace('Math::add'), getTrace('UnionTypes::assert')]);

                $closure = function () {
                    UnionTypes::assert('1.2', ['int', 'float']);
                };

                expect($closure)->toThrow(new TypeError($message));
            });
        });

        describe("UnionTypes::assert('1.2', ['int', 'float', 'string'])", function () {
            it("should pass", function () {
                expect(UnionTypes::assert('1.2', ['int', 'float', 'string']))->toBe(null);
            });
        });
    });

    describe('::is($value, array $types): void', function () {
        describe("UnionTypes::is(1.2, ['int', 'float'])", function () {
            it("should return `true`", function () {
                expect(UnionTypes::is(1.2, ['int', 'float']))->toBe(true);
            });
        });

        describe("UnionTypes::is('1.2', ['int', 'float'])", function () {
            it("should return `false`", function () {
                expect(UnionTypes::is('1.2', ['int', 'float']))->toBe(false);
            });
        });

        describe("UnionTypes::is('1.2', ['int', 'float', 'string'])", function () {
            it("should return `true`", function () {
                expect(UnionTypes::is('1.2', ['int', 'float', 'string']))->toBe(true);
            });
        });

        describe("UnionTypes::is(\$today, [\DateTime::class, 'string'])", function () {
            it("should return `true`", function () {
                $today = Time::today();
                expect(UnionTypes::is($today, [\DateTime::class]))->toBe(true);
            });
        });

        describe("UnionTypes::is(\$today, [\DateTime::class, 'string'], ['instanceOf' => false])", function () {
            it("should return `false`", function () {
                $today = Time::today();
                // disable the `instanceOf` check
                expect(UnionTypes::is($today, [\DateTime::class], ['instanceOf' => false]))->toBe(false);
            });
        });
    });

    describe('::assertFuncArg(string $argName, array $types): void', function () {
        describe("UnionTypes::assertFuncArg('kahlanClosureDoesNotExist', ['int', 'float', 'string'])", function () {
            $message = 'Method Kahlan\\Cli\\Kahlan::{closure}() does not exist';
            it("should throw ReflectionException $message", function () use ($message) {
                $closure = function () {
                    UnionTypes::assertFuncArg('kahlanClosureDoesNotExist', ['int', 'float', 'string']);
                };

                expect($closure)->toThrow(new ReflectionException($message));
            });
        });

        describe("UnionTypes::assertFuncArg('outOfScope', ['int', 'float', 'string'])", function () {
            $class = "UnionTypes\UnionTypes";
            $function = "assertFuncArg";
            $message = "The `$class::$function()` must be invoked **INSIDE** a function or method";
            it("should throw FatalError $message", function () use ($message) {
                // to trigger the FatalError('OUT OF SCOPE'), we need to force the `debug_backtrace` function to
                // return only one stack trace
                allow('debug_backtrace')
                    ->toBeCalled()
                    ->andReturn([getTrace('UnionTypes::assertFuncArg')]);

                $closure = function () {
                    UnionTypes::assertFuncArg('outOfScope', ['int', 'float', 'string']);
                };

                expect($closure)->toThrow(new FatalError($message));
            });
        });

        describe("UnionTypes::assertFuncArg('noArgsFound', ['int', 'float', 'string'])", function () {
            $class = "UnionTypes\Spec\Fixtures\Math";
            $function = "random";
            $message = "The `$class::$function()` method doesn't accept any argument";
            it("should throw FatalError $message", function () use ($message) {
                // to trigger the FatalError('noArgsFound'), we need to force the `debug_backtrace` function to
                // return `trace[1]` with a function without argument like `Math::random()`
                // see `UnionTypes\Spec\Fixtures\Math::random()`
                allow('debug_backtrace')
                    ->toBeCalled()
                    ->andReturn([getTrace('UnionTypes::assertFuncArg'), getTrace('Math::random')]);

                $closure = function () {
                    UnionTypes::assertFuncArg('noArgsFound', ['int', 'float']);
                };
                expect($closure)->toThrow(new FatalError($message, 1));
            });
        });

        describe("UnionTypes::assertFuncArg('c', ['int', 'float'])", function () {
            $class = "UnionTypes\Spec\Fixtures\Math";
            $function = "add";
            $funcArgs = ['a', 'b'];
            $invalidArgName = 'c';
            $callback = function ($arg) {
                return "'" . $arg . "'";
            };
            $message = sprintf(
                "Invalid argument name `%s` for `%s::%s()`, try one of those values `%s`",
                $invalidArgName,
                $class,
                $function,
                implode(', ', array_map($callback, $funcArgs))
            );
            it("should throw FatalError $message", function () use ($message, $invalidArgName) {
                // to trigger the FatalError('InvalidArgumentName'), we need to force the `debug_backtrace` function to
                // use `trace[1]`, `Math::add($a, $b)` and assertFuncArg with invalid argument name `c`.
                // see `UnionTypes\Spec\Fixtures\Math::add($a, $b)`
                allow('debug_backtrace')
                    ->toBeCalled()
                    ->andReturn([getTrace('UnionTypes::assertFuncArg'), getTrace('Math::add')]);

                $closure = function () use ($invalidArgName) {
                    UnionTypes::assertFuncArg($invalidArgName, ['int', 'float', 'string']);
                };

                expect($closure)->toThrow(new FatalError($message));
            });
        });

        describe("UnionTypes::assertFuncArg('a', ['int', 'float'])", function () {
            $class = "UnionTypes\Spec\Fixtures\Math";
            $function = "add";
            $funcArgs = ['a', 'b'];
            $types = ['int', 'float'];
            $argName = 'a';
            $argValue = '1.2';
            $argIndex = 0;
            $argOffset = $argIndex + 1;
            $argType = 'string';
            $message = sprintf(
                'Argument `%s` passed to `%s::%s(%s)` must be of the union type `%s`, `%s` given',
                $argOffset,
                $class,
                $function,
                StackTrace::funcArgsEllipsis($argName, $funcArgs, ...$types),
                implode('|', $types),
                $argType
            );

            $it = function () use ($message, $argIndex, $argValue, $argName, $types) {
                // to trigger the TypeError(), we need to force the `debug_backtrace` function to
                // return `trace[1]` with invalid argument type `$a` `string`, valid union types are `int` or `float`
                // see `UnionTypes\Spec\Fixtures\Math::add($a, $b)`
                $overwriteTraceWith = [
                    "args" => [
                        $argIndex => $argValue,
                    ],
                ];
                $backTrace = [getTrace('UnionTypes::assertFuncArg'), getTrace('Math::add', $overwriteTraceWith)];
                allow('debug_backtrace')->toBeCalled()->andReturn($backTrace);

                $closure = function () use ($argName, $types) {
                    UnionTypes::assertFuncArg($argName, $types);
                };

                expect($closure)->toThrow(new TypeError($message, 1));
            };
            it("should throw FatalError $message", $it);
        });
    });
});

describe("StackTrace", function () {
    describe('::funcArgsEllipsis(string $argName, array $funcArgs, string ...$types): string', function () {
        describe("StackTrace::funcArgsEllipsis('x', [0 => 'x'], 'int', 'float')", function () {
            it('should return `int|float $x`', function () {
                $expect = StackTrace::funcArgsEllipsis('x', [0 => 'x'], 'int', 'float');
                $toBe = 'int|float $x';
                expect($expect)->toBe($toBe);
            });
        });

        describe("StackTrace::funcArgsEllipsis('a', [0 => 'a', 1 => 'b'], 'int', 'float')", function () {
            it('should return `int|float $a, ...`', function () {
                $expect = StackTrace::funcArgsEllipsis('a', [0 => 'a', 1 => 'b'], 'int', 'float');
                $toBe = 'int|float $a, ...';
                expect($expect)->toBe($toBe);
            });
        });

        describe("StackTrace::funcArgsEllipsis('b', [0 => 'a', 1 => 'b'], 'int', 'float')", function () {
            it('should return `..., int|float $b`', function () {
                $expect = StackTrace::funcArgsEllipsis('b', [0 => 'a', 1 => 'b'], 'int', 'float');
                $toBe = '..., int|float $b';
                expect($expect)->toBe($toBe);
            });
        });

        describe("StackTrace::funcArgsEllipsis('b', [0 => 'a', 1 => 'b', 2 => 'c'], 'int', 'float')", function () {
            it('should return `..., int|float $b, ...`', function () {
                $expect = StackTrace::funcArgsEllipsis('b', [0 => 'a', 1 => 'b', 2 => 'c'], 'int', 'float');
                $toBe = '..., int|float $b, ...';
                expect($expect)->toBe($toBe);
            });
        });
    });

    describe('::fileRelativeToWorkspace(string $file): string', function () {
        $file = '/path/to/app/src/Table/PostsTable.php';
        $const = 'UnionTypes.PATH_TO_APP';
        $PATH_TO_APP = '/path/to/app/';

        describe("StackTrace::fileRelativeToWorkspace('$file')", function () use ($file, $const) {
            $it = function () use ($file, $const) {
                allow('defined')->toBeCalled()->with($const)->andReturn(false);
                expect(StackTrace::fileRelativeToWorkspace($file))->toBe($file);
            };

            it("should return the passed file `$file` since `$const` const hasn't been defined yet", $it);
        });

        describe("StackTrace::fileRelativeToWorkspace('$file')", function () use ($file, $const, $PATH_TO_APP) {
            $fileToBe = 'src/Table/PostsTable.php';
            $message = sprintf(
                "should return friendly editor format `%s` since `%s` const is defined as `%s`",
                $fileToBe,
                $const,
                $PATH_TO_APP // phpcs:ignore Zend.NamingConventions.ValidVariableName.StringVarNotCamelCaps
            );
            $it = function () use ($file, $const, $fileToBe, $PATH_TO_APP) {
                allow('defined')->toBeCalled()->with($const)->andReturn(true);
                allow('constant')->toBeCalled()->with($const)->andReturn($PATH_TO_APP);
                expect(StackTrace::fileRelativeToWorkspace($file))->toBe($fileToBe);
            };
            it($message, $it);
        });

        describe("StackTrace::fileRelativeToWorkspace('$file')", function () use ($file, $const) {
            $message = "should return the passed file `$file` since `$const` const isn't a string";

            it($message, function () use ($file, $const) {
                allow('defined')->toBeCalled()->with($const)->andReturn(true);
                allow('constant')->toBeCalled()->with($const)->andReturn([]);
                expect(StackTrace::fileRelativeToWorkspace($file))->toBe($file);
            });
        });
    });

    describe('::findStackTraceByIndex(array $fullStackTrace, int $index): ?array', function () {
        describe('StackTrace::findStackTraceByIndex([0 => [...], 1 => [...]], 2)', function () {
            it("should return `null` since the index `2` doesn't exist in the passed backtrace array", function () {
                expect(StackTrace::findStackTraceByIndex([0 => ['...'], 1 => ['...']], 2))->toBeNull();
            });
        });
    });
});
