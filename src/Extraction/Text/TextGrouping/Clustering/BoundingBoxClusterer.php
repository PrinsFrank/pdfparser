<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\Text\TextGrouping\Clustering;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;

class BoundingBoxClusterer {
    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @return list<Cluster>
     */
    public static function cluster(array $positionedTextElements, Page $page): array {
        usort(
            $positionedTextElements,
            fn(PositionedTextElement $a, PositionedTextElement $b): int => $b->absoluteMatrix->offsetY <=> $a->absoluteMatrix->offsetY,
        );

        $processedIndices = $clusters = [];
        $nrOfItems = count($positionedTextElements);
        for ($i = 0; $i < $nrOfItems; $i++) {
            if (isset($processedIndices[$i])) {
                continue;
            }

            $firstClusterElement = $positionedTextElements[$i];
            $cluster = [$firstClusterElement];
            $processedIndices[$i] = true;

            $clusterLeft = $firstClusterElement->absoluteMatrix->offsetX;
            $clusterRight = $clusterLeft + $firstClusterElement->getAdvanceWidth($page);
            $clusterBottom = $firstClusterElement->absoluteMatrix->offsetY;
            $clusterTop = $clusterBottom + $firstClusterElement->getHeight();

            $previousPassResultedInNewElements = true;
            while ($previousPassResultedInNewElements) {
                $previousPassResultedInNewElements = false;

                for ($j = $i + 1; $j < $nrOfItems; $j++) {
                    if (isset($processedIndices[$j])) {
                        continue;
                    }

                    $candidate = $positionedTextElements[$j];
                    $candidateRight = $candidate->absoluteMatrix->offsetX + $candidate->getAdvanceWidth($page);
                    $candidateTop = $candidate->absoluteMatrix->offsetY + $candidate->getHeight();
                    if (max(0, $clusterLeft - $candidateRight, $candidate->absoluteMatrix->offsetX - $clusterRight) <= 5
                        && max(0, $clusterBottom - $candidateTop, $candidate->absoluteMatrix->offsetY - $clusterTop) <= 7) {
                        $cluster[] = $candidate;
                        $processedIndices[$j] = true;
                        $previousPassResultedInNewElements = true;

                        $clusterLeft = min($clusterLeft, $candidate->absoluteMatrix->offsetX);
                        $clusterRight = max($clusterRight, $candidateRight);
                        $clusterBottom = min($clusterBottom, $candidate->absoluteMatrix->offsetY);
                        $clusterTop = max($clusterTop, $candidateTop);
                    }
                }
            }

            $clusters[] = new Cluster($cluster, $clusterLeft, $clusterRight, $clusterBottom, $clusterTop);
        }

        usort(
            $clusters,
            function (Cluster $a, Cluster $b): int {
                $aHeight = $a->top - $a->bottom;
                $bHeight = $b->top - $b->bottom;
                $smallestHeight = min($aHeight, $bHeight);
                if ($smallestHeight > 0) {
                    $overlap = min($a->top, $b->top) - max($a->bottom, $b->bottom);
                    $overlapPercentage = ($overlap / $smallestHeight) * 100;
                    if ($overlapPercentage >= 90) {
                        return $a->left <=> $b->left;
                    }
                }

                return $b->top <=> $a->top;
            },
        );

        return $clusters;
    }
}
