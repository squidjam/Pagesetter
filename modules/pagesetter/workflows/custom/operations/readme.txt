Workflow Operations
-------------------

This directory contains workflow operations - one file for each possible
operation. Each file must be named exactly as the corresponding operation and
must contain at least a function named "pagesetter_operation_OperationName".
That function will then be invoked when the operation needs to be performed.

Many of the operations are nothing but a wrapper to a Pagesetter API funktion,
but in this way it is possible to add and manage operations decentralized
without having to update a single central file.
