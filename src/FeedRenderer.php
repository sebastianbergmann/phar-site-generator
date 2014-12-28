<?php
namespace SebastianBergmann\PharSiteGenerator;

class FeedRenderer extends AbstractRenderer
{
    /**
     * @param ReleaseCollection $releases
     */
    public function render(ReleaseCollection $releases)
    {
        $feedTemplate     = new \Text_Template(__DIR__ . '/../templates/feed.xml');
        $feedItemTemplate = new \Text_Template(__DIR__ . '/../templates/item.xml');
        $rdfList          = '';
        $rdfItems         = '';

        foreach ($releases->latestReleasesSortedByDate() as $release) {
            $rdfList .= sprintf(
                '    <rdf:li rdf:resource="%s/%s-%s.phar"/>' . "\n",
                $this->site(),
                $release->package(),
                $release->version()
            );

            $feedItemTemplate->setVar(
                [
                    'site'    => $this->site(),
                    'package' => $release->package(),
                    'version' => $release->version(),
                    'date'    => $release->date(),
                    'content' => ''
                ]
            );

            $rdfItems .= $feedItemTemplate->render();
        }

        $feedTemplate->setVar(
            [
                'items_list' => $rdfList,
                'items'      => $rdfItems,
                'site'       => $this->site(),
                'email'      => $this->email()
            ]
        );

        $feedTemplate->renderTo($this->target());
    }
}
