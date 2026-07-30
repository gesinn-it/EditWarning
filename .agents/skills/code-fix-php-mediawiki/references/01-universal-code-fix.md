**Procedure — code:fix**

1.  Reproduce the bug with a failing test first. This test is the proof
    the bug exists.

2.  Run that test against the unmodified code and observe it fail, for
    the reason the bug describes. A test that was only written, never
    executed red, is not proof of anything — it may pass by accident
    (wrong mock, wrong assertion, code path never reached) and give
    false confidence in the fix that follows.

3.  Fix the code until the test passes.

4.  Never fix code without a reproducing test — you cannot verify the
    fix is correct.

5.  Before considering the fix complete, re-read the test and ask: does
    every mock/stub in it actually exercise the real code path where the
    bug lives, or does it substitute away the exact behavior under test?
    A mock that returns a fixed value regardless of its input can
    silently skip the line that matters.

6.  If the fix addresses a reported issue: after pushing, close the
    issue in the issue tracker with a comment referencing the commit.
