<?php

namespace Computerrock\SonataTranslationBundle\Admin;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;

class ORMTranslationAdmin extends TranslationAdmin
{

    protected function configureDatagridFilters(DatagridMapper $filter)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('Lexik\Bundle\TranslationBundle\Entity\File');

        $domains = array();
        $domainsQueryResult = $em->createQueryBuilder()
            ->select('DISTINCT t.domain')->from('\Lexik\Bundle\TranslationBundle\Entity\File', 't')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        array_walk_recursive(
            $domainsQueryResult,
            function ($domain) use (&$domains) {
                $domains[$domain] = $domain;
            }
        );
        ksort($domains);

        $filter
            ->add(
                'locale',
                'doctrine_orm_callback',
                array(
                    'callback'      => function (ProxyQuery $queryBuilder, $alias, $field, $options) {
                        /* @var $queryBuilder \Doctrine\ORM\QueryBuilder */
                        if (!isset($options['value']) || empty($options['value'])) {
                            return;
                        }
                        // use on to filter locales
                        $this->joinTranslations($queryBuilder, $alias, $options['value']);
                    },
                    'field_options' => array(
                        'choices'  => $this->formatLocales($this->managedLocales),
                        'required' => false,
                        'multiple' => true,
                        'expanded' => false
                    ),
                    'field_type'    => 'choice',
                )
            )
            ->add(
                'show_non_translated_only',
                'doctrine_orm_callback',
                array
                (
                    'callback'      => function (ProxyQuery $queryBuilder, $alias, $field, $options) {
                        /* @var $queryBuilder \Doctrine\ORM\QueryBuilder */
                        if (!isset($options['value']) || empty($options['value']) || false === $options['value']) {
                            return;
                        }
                        $this->joinTranslations($queryBuilder, $alias);

                        foreach ($this->getEmptyFieldPrefixes() as $prefix) {
                            if (empty($prefix)) {
                                $queryBuilder->orWhere('translations.content IS NULL');
                            } else {
                                $queryBuilder->orWhere('translations.content LIKE :content')->setParameter(
                                    'content',
                                    $prefix . '%'
                                );
                            }

                        }
                    },
                    'field_options' => array(
                        'required' => true,
                        'value'    => $this->getNonTranslatedOnly(),
                    ),
                    'field_type'    => 'checkbox',
                )
            )
            ->add('key', 'doctrine_orm_string')
            ->add(
                'domain',
                'doctrine_orm_choice',
                array(
                    'field_options' => array(
                        'choices'     => $domains,
                        'required'    => true,
                        'multiple'    => false,
                        'expanded'    => false,
                        'empty_value' => 'all',
                        'empty_data'  => 'all'
                    ),
                    'field_type'    => 'choice',
                )
            )
            ->add(
                'content',
                'doctrine_orm_callback',
                array
                (
                    'callback'   => function (ProxyQuery $queryBuilder, $alias, $field, $options) {
                        /* @var $queryBuilder \Doctrine\ORM\QueryBuilder */
                        if (!isset($options['value']) || empty($options['value'])) {
                            return;
                        }
                        $this->joinTranslations($queryBuilder, $alias);
                        $queryBuilder->andWhere('translations.content LIKE :content')->setParameter(
                            'content',
                            '%' . $options['value'] . '%'
                        );
                    },
                    'field_type' => 'text',
                    'label'      => 'content',
                )
            );
    }

    /**
     * @param ProxyQuery $queryBuilder
     * @param String     $alias
     */
    private function joinTranslations(ProxyQuery $queryBuilder, $alias, array $locales = null)
    {
        $alreadyJoined = false;
        $joins = $queryBuilder->getDQLPart('join');
        if (array_key_exists($alias, $joins)) {
            $joins = $joins[$alias];
            foreach ($joins as $join) {
                if (strpos($join->__toString(), "$alias.translations ")) {
                    $alreadyJoined = true;
                }
            }
        }
        if (!$alreadyJoined) {
            /** @var QueryBuilder $queryBuilder */
            if ($locales) {
                $queryBuilder->leftJoin(sprintf('%s.translations', $alias), 'translations', 'WITH', 'translations.locale = :locales');
                $queryBuilder->setParameter('locales', $locales);
            } else {
                $queryBuilder->leftJoin(sprintf('%s.translations', $alias), 'translations');
            }
        }
    }

    /**
     * @return array
     */
    private function formatLocales(array $locales)
    {
        $formattedLocales = array();
        array_walk_recursive(
            $locales,
            function ($language) use (&$formattedLocales) {
                $formattedLocales[$language] = $language;
            }
        );

        return $formattedLocales;
    }
}
