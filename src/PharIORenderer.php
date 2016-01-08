<?php
/*
 * This file is part of phar-site-generator.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\PharSiteGenerator;

/**
 * Rendering code for phar.io repository xml
 *
 * @author Arne Blankerts <arne@blankerts.de>
 * @package SebastianBergmann\PharSiteGenerator
 */
class PharIORenderer extends AbstractRenderer
{
    /**
     * @var \DOMDocument
     */
    private $repository;

    /**
     * @var \DOMXPath
     */
    private $xp;
    /**
     * @param ReleaseCollection $releases
     */
    public function render(ReleaseCollection $releases)
    {
        $this->initRepository();
        foreach ($releases->allReleases() as $release) {
            $this->addRelease($release);
        }
        $this->saveRepository();
    }

    private function addRelease(Release $release)
    {
        $url = sprintf(
            'https://%s/%s-%s.phar',
            $this->domain(),
            $release->package(),
            $release->version()
        );

        $releaseNode = $this->addElement('release');
        $releaseNode->setAttribute('version', $release->version());
        $releaseNode->setAttribute('url', $url);

        $signatureNode = $this->addElement('signature');
        $signatureNode->setAttribute('type', 'gpg');
        $releaseNode->appendChild($signatureNode);

        $hashNode = $this->addElement('hash');
        $hashNode->setAttribute('type', 'sha-256');
        $hashNode->setAttribute('value', $release->sha256());
        $releaseNode->appendChild($hashNode);

        $container = $this->getContainer($release->package());
        if ($container->hasChildNodes()) {
            $container->insertBefore(
                $releaseNode,
                $container->firstChild
            );
        } else {
            $container->appendChild($releaseNode);
        }
    }

    private function getContainer($package)
    {
        $result = $this->xp->query(
            sprintf('//phive:phar[@name="%s"]', $package)
        );
        if ($result->length > 0) {
            return $result->item(0);
        }
        $pharNode = $this->addElement('phar');
        $pharNode->setAttribute('name', $package);
        $this->repository->documentElement->appendChild($pharNode);

        return $pharNode;
    }

    private function initRepository()
    {
        $this->repository = new \DOMDocument('1.0', 'UTF-8');
        $this->repository->load(__DIR__ . '/templates/phive.xml');
        $this->xp = new \DOMXPath($this->repository);
        $this->xp->registerNamespace('phive', 'https://phar.io/repository');
    }

    private function addElement($name)
    {
        return $this->repository->createElementNS('https://phar.io/repository', $name);
    }

    private function saveRepository()
    {
        $this->repository->preserveWhiteSpace = false;
        $this->repository->formatOutput = true;
        $this->repository->save($this->target());
    }
}
