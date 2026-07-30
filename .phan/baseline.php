<?php
/**
 * This is an automatically generated baseline for Phan issues.
 * When Phan is invoked with --load-baseline=path/to/baseline.php,
 * The pre-existing issues listed in this file won't be emitted.
 *
 * This file can be updated by invoking Phan with --save-baseline=path/to/baseline.php
 * (can be combined with --load-baseline)
 */
return [
	// # Issue statistics:
	// PhanTypeMismatchArgumentInternal : 10+ occurrences
	// SecurityCheck-XSS : 6 occurrences
	// MediaWikiNoBaseException : 2 occurrences
	// PhanTypeMismatchArgument : 2 occurrences
	// PhanUndeclaredClassMethod : 2 occurrences
	// PhanUndeclaredTypeParameter : 2 occurrences
	// PhanParamSignatureMismatch : 1 occurrence
	// PhanParamTooMany : 1 occurrence
	// PhanTypeInvalidLeftOperandOfAdd : 1 occurrence
	// PhanTypeMismatchReturn : 1 occurrence
	// PhanTypeMismatchReturnProbablyReal : 1 occurrence

	// Currently, file_suppressions and directory_suppressions are the only supported suppressions
	'file_suppressions' => [
		'src/EditWarning.php' => ['PhanParamTooMany', 'PhanTypeInvalidLeftOperandOfAdd', 'PhanTypeMismatchArgumentInternal', 'PhanTypeMismatchReturn', 'PhanTypeMismatchReturnProbablyReal', 'PhanUndeclaredClassMethod', 'PhanUndeclaredTypeParameter'],
		'src/EditWarningApi.php' => ['PhanTypeMismatchArgument'],
		'src/EditWarningMessage.php' => ['MediaWikiNoBaseException', 'SecurityCheck-XSS'],
		'src/EditWarningMsg.php' => ['PhanParamSignatureMismatch', 'SecurityCheck-XSS'],
	],
	// 'directory_suppressions' => ['src/directory_name' => ['PhanIssueName1', 'PhanIssueName2']] can be manually added if needed.
	// (directory_suppressions will currently be ignored by subsequent calls to --save-baseline, but may be preserved in future Phan releases)
];
