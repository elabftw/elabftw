#!/usr/bin/env bash
#
# @author Marcel Bolten <github@marcelbolten.de>
# @copyright 2023 Nicolas CARPi
# @see https://www.elabftw.net Official website
# @license AGPL-3.0
# @package elabftw
#
# This script will merge codecoverage results from the codeception unit/api and cypress e2e tests

cd /elabftw/tests/_output
mkdir -p merge_cov/coverage-html-merged
cp coverage.serialized merge_cov/unit_api.cov
cp c3tmp/codecoverage.serialized merge_cov/cypress.cov

# modify cypress coverage file to be able to merge it with phpcov
cd merge_cov
sed -i "1i <?php return \\\unserialize(<<<'END_OF_COVERAGE_SERIALIZATION'" cypress.cov
echo -e '\nEND_OF_COVERAGE_SERIALIZATION\n);' >> cypress.cov

# merge files and create reports
phpcov merge --clover merged_coverage.xml .
phpcov merge --html coverage-html-merged .
