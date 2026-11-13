---
description: Onboards GSD to the repository by creating context files, mapping the project structure, and establishing repository conventions.
---

# GSD Onboard Workflow

This workflow executes the codebase mapping and initialization process for GSD Core.

1. Inspects the repository and identifies the tech stack.
2. Extracts key insights about architecture, conventions, and state.
3. Generates a generalized `GSD_CONTEXT.md` (or similar environment memory) to provide AI coding agents with high-level awareness.
4. Initializes `.cursorrules` and standard `.agent` directories.
5. Ingests required documentation for seamless AI development.

// turbo-all
1. Create a `GSD_CONTEXT.md` file in the root directory.
2. Initialize `.cursorrules` in the root directory.
