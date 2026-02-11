#!/usr/bin/env bash
set -euo pipefail

log_file="$(mktemp)"
trap 'rm -f "${log_file}"' EXIT

command=(
  vendor/bin/pest
  --mutate
  --parallel
  --compact
  --no-progress
  --class=Cline\\Ruler\\Core\\RuleEvaluator,Cline\\Ruler\\Exceptions\\RuleEvaluatorException,Cline\\Ruler\\Enums\\RuleErrorCode,Cline\\Ruler\\Enums\\RuleErrorPhase
  --min=55
  --ignore-min-score-on-zero-mutations
)

set +e
"${command[@]}" >"${log_file}" 2>&1
status=$?
set -e

echo "Mutation summary:"
grep -E "Mutating application files|Mutations for|Mutations:|Score:|Duration:|Parallel:" "${log_file}" || true

if [ ${status} -ne 0 ]; then
  echo ""
  echo "Mutation run failed. Showing tail of full output:"
  tail -n 120 "${log_file}"
fi

exit ${status}
