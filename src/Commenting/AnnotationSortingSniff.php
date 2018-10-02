<?php
declare(strict_types=1);

namespace NatePage\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\Annotation;
use SlevomatCodingStandard\Helpers\AnnotationHelper;
use SlevomatCodingStandard\Helpers\DocCommentHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;

/**
 * @SuppressWarnings(PHPMD.StaticAccess) Inherited SlevomatCodingStandards
 */
class AnnotationSortingSniff implements Sniff
{
    /**
     * @var string
     */
    public const CODE_ANNOTATION_SORT_ALPHABETICALLY = 'AnnotationSortAlphabetically';
    /**
     * @var string
     */
    public const CODE_SHOULD_BE_START_OF_DOC = 'AnnotationStartOfDoc';
    /**
     * @var string[]
     */
    public $alwaysTopAnnotations = [];
    /**
     * @var \PHP_CodeSniffer\Files\File
     */
    private $phpcsFile;

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $openPointer
     *
     * @return void
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @phpcsSuppress EoneoPay.Commenting.FunctionComment.ScalarTypeHintMissing
     */
    public function process(File $phpcsFile, $openPointer): void
    {
        if (DocCommentHelper::isInline($phpcsFile, $openPointer)) {
            return;
        }
        $this->phpcsFile = $phpcsFile;
        $tokens = $phpcsFile->getTokens();
        $commentCloser = $tokens[$openPointer]['comment_closer'];
        $fcStartPointer = TokenHelper::findNextExcluding(
            $phpcsFile,
            [\T_DOC_COMMENT_WHITESPACE, \T_DOC_COMMENT_STAR],
            $openPointer + 1,
            $commentCloser
        );
        if ($fcStartPointer === null) {
            return;
        }
        $annotations = $this->getAnnotations($openPointer);
        $this->checkAnnotationsAreSorted($annotations);
    }

    /**
     * @return mixed[]
     */
    public function register(): array
    {
        return [
            \T_DOC_COMMENT_OPEN_TAG
        ];
    }

    /**
     * Check annotations are sorted.
     *
     * @param \SlevomatCodingStandard\Helpers\Annotation[] $annotations
     *
     * @return void
     */
    private function checkAnnotationsAreSorted(array $annotations): void
    {
        if (empty($annotations)) {
            return;
        }
        $previousAnnotation = null;
        foreach ($annotations as $annotation) {
            $currentAnnotation = $this->getAnnotationName($annotation);
            if ($previousAnnotation === null) {
                $previousAnnotation = $currentAnnotation;
                continue;
            }
            // Previous is always top. Current is not. Do nothing.
            if (\in_array($previousAnnotation, $this->alwaysTopAnnotations, true) === true &&
                \in_array($currentAnnotation, $this->alwaysTopAnnotations, true) === false) {
                $previousAnnotation = $currentAnnotation;
                continue;
            }
            $alwaysTop = $this->checkAnnotationsShouldBeOnTop(
                $previousAnnotation,
                $currentAnnotation,
                $annotation->getStartPointer()
            );
            // Current is always top. Current is not. Should switch.
            if ($alwaysTop === true) {
                $previousAnnotation = $currentAnnotation;
                continue;
            }
            $this->compareAnnotations($previousAnnotation, $currentAnnotation, $annotation->getStartPointer());
            $previousAnnotation = $currentAnnotation;
        }
    }

    /**
     * Checxk annotations that should be always on top.
     *
     * @param string $previousAnnotation
     * @param string $currentAnnotation
     * @param int $currentPointer
     *
     * @return bool
     */
    private function checkAnnotationsShouldBeOnTop(
        string $previousAnnotation,
        string $currentAnnotation,
        int $currentPointer
    ): bool {
        // Current is always top. Previous is not.
        if (\in_array($previousAnnotation, $this->alwaysTopAnnotations, true) === false &&
            \in_array($currentAnnotation, $this->alwaysTopAnnotations, true) === true) {
            $this->phpcsFile->addError(
                \sprintf(
                    'Always on top annotations (%s) should be placed above other annotations, found "%s" is before "%s".',
                    $previousAnnotation,
                    $currentAnnotation,
                    implode(', ', $this->alwaysTopAnnotations)
                ),
                $currentPointer,
                self::CODE_SHOULD_BE_START_OF_DOC
            );

            return true;
        }

        return false;
    }

    /**
     * Compare previous and current annotation.
     *
     * @param string $prevAnnotation
     * @param string $currAnnotation
     * @param int $currentPointer
     *
     * @return bool
     */
    private function compareAnnotations(string $prevAnnotation, string $currAnnotation, int $currentPointer): bool
    {
        if (\strcasecmp($prevAnnotation, $currAnnotation) <= 0) {
            return true;
        }
        $this->phpcsFile->addError(
            \sprintf(
                'Expected annotations should be alphabetically sorted, found "%s" is before "%s".',
                $prevAnnotation,
                $currAnnotation
            ),
            $currentPointer,
            self::CODE_ANNOTATION_SORT_ALPHABETICALLY
        );

        return false;
    }

    /**
     * Get annotation name.
     *
     * @param \SlevomatCodingStandard\Helpers\Annotation $annotation
     *
     * @return string
     */
    private function getAnnotationName(Annotation $annotation): string
    {
        $exploded = \explode('\\', $annotation->getName());

        return \reset($exploded);
    }

    /**
     * Get annotations.
     *
     * @param int $openPointer
     *
     * @return \SlevomatCodingStandard\Helpers\Annotation[]
     */
    private function getAnnotations(int $openPointer): array
    {
        $annotations = \array_merge(
            [],
            ...\array_values(AnnotationHelper::getAnnotations($this->phpcsFile, $openPointer))
        );

        return $annotations;
    }
}