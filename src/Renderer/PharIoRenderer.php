<?php declare(strict_types=1);
/*
 * This file is part of phar-site-generator.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\PharSiteGenerator;

use function assert;
use function sprintf;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

final class PharIoRenderer extends AbstractRenderer
{
    private DOMDocument $repository;
    private DOMXPath $xp;

    public function render(ReleaseCollection $releases): void
    {
        $this->initRepository();

        foreach ($releases->allReleases() as $release) {
            $this->addRelease($release);
        }

        $this->saveRepository();
    }

    private function addRelease(Release $release): void
    {
        $url = sprintf(
            'https://%s/%s-%s.phar',
            $this->domain(),
            $release->package(),
            $release->version(),
        );

        $releaseNode = $this->addElement('release');

        assert($releaseNode instanceof DOMElement);

        $releaseNode->setAttribute('version', $release->version());
        $releaseNode->setAttribute('url', $url);

        $signatureNode = $this->addElement('signature');

        assert($signatureNode instanceof DOMElement);

        $signatureNode->setAttribute('type', 'gpg');
        $releaseNode->appendChild($signatureNode);

        $hashNode = $this->addElement('hash');

        assert($hashNode instanceof DOMElement);

        $hashNode->setAttribute('type', 'sha-256');
        $hashNode->setAttribute('value', $release->sha256());
        $releaseNode->appendChild($hashNode);

        $container = $this->container($release->package());

        if ($container->hasChildNodes()) {
            $container->insertBefore(
                $releaseNode,
                $container->firstChild,
            );
        } else {
            $container->appendChild($releaseNode);
        }
    }

    private function container(string $package): DOMNode
    {
        $result = $this->xp->query(
            sprintf('//phive:phar[@name="%s"]', $package),
        );

        assert($result !== false);

        if ($result->length > 0) {
            $result = $result->item(0);

            assert($result !== null);

            return $result;
        }

        $pharNode = $this->addElement('phar');

        assert($pharNode instanceof DOMElement);

        $pharNode->setAttribute('name', $package);

        assert($this->repository->documentElement !== null);

        $this->repository->documentElement->appendChild($pharNode);

        return $pharNode;
    }

    private function initRepository(): void
    {
        $this->repository = new DOMDocument('1.0', 'UTF-8');

        $this->repository->load(__DIR__ . '/../templates/phive.xml');

        $this->xp = new DOMXPath($this->repository);

        $this->xp->registerNamespace('phive', 'https://phar.io/repository');
    }

    private function addElement(string $name): DOMNode
    {
        return $this->repository->createElementNS('https://phar.io/repository', $name);
    }

    private function saveRepository(): void
    {
        $this->repository->preserveWhiteSpace = false;
        $this->repository->formatOutput       = true;
        $this->repository->save($this->target());
    }
}
