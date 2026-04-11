# Permissions Catalog

Every permission string registered in `database/seeders/PermissionSeeder.php`,
what it protects, and which UI/API surface enforces it. The `Super Admin`
role implicitly has everything via the `Gate::before` check in
`AppServiceProvider`, so these gates only apply to non-admin users.

---

## Quizzes

| Permission | Protects | Enforcement |
| --- | --- | --- |
| `quiz.view` | `/admin/quizzes`, builder, results reading | `can:quiz.view` route middleware |
| `quiz.create` | Creating new quizzes | `QuizPolicy::create` + middleware |
| `quiz.edit` | Updating a quiz / sections / questions | Policy + middleware |
| `quiz.delete` | Soft-deleting a quiz | Policy + middleware |
| `quiz.publish` | Publish / unpublish toggle | Policy |
| `quiz.duplicate` | Clone a quiz | Policy |

## Question bank

| Permission | Protects | Enforcement |
| --- | --- | --- |
| `questionbank.view` | `/admin/questions` | `can:questionbank.view` |
| `questionbank.create` | Creating questions (all types) | Policy |
| `questionbank.edit` | Updating questions | Policy |
| `questionbank.delete` | Deleting questions | Policy |
| `questionbank.import` | Bulk import endpoint | Policy |
| `questionbank.export` | Bulk export endpoint | Policy |

## Invitations

| Permission | Protects | Enforcement |
| --- | --- | --- |
| `invite.view` | `/admin/quizzes/{quiz}/invitations` listing | Policy |
| `invite.create` | Generating new invitation tokens | Policy |
| `invite.revoke` | Revoking existing invitations | Policy |

## Candidates

| Permission | Protects | Enforcement |
| --- | --- | --- |
| `candidate.view` | `/admin/candidates` list | Policy (UI stub) |
| `candidate.delete` | Deleting candidate accounts | Policy |
| `candidate.export` | CSV export of candidates | Policy |

## Results

| Permission | Protects | Enforcement |
| --- | --- | --- |
| `results.view` | `/admin/results` index + quiz results tables | `can:results.view` |
| `results.export` | `/admin/results/{quiz}/export` CSV download | `can:results.export` |
| `results.viewSuspicious` | Showing suspicious event counts + drill-down | Controller-level `$user->hasPermissionTo(...)` |
| `results.viewSnapshots` | Showing camera snapshots gallery | Controller-level check |

## RLHF review

| Permission | Protects | Enforcement |
| --- | --- | --- |
| `rlhf.view` | `/admin/rlhf/review/{answer}` detail page | `can:rlhf.view` |
| `rlhf.score` | Saving a draft review (POST `store`) | Route-level `can:rlhf.score` |
| `rlhf.finalize` | Locking a review + triggering recalculation | Route-level `can:rlhf.finalize` |

## Coding review

| Permission | Protects | Enforcement |
| --- | --- | --- |
| `coding.view` | `/admin/coding/review/{answer}` detail page | `can:coding.view` |
| `coding.rerun` | Re-dispatching the coding run job | Route-level `can:coding.rerun` |
| `coding.override` | Manually overriding a reviewer score (logs reason) | Route-level `can:coding.override` |

## Users & roles

| Permission | Protects | Enforcement |
| --- | --- | --- |
| `users.view` | `/admin/users` list | `can:users.view` |
| `users.invite` | Inviting a new admin user | Policy |
| `users.edit` | Updating user profile / roles | Policy |
| `users.deactivate` | Deactivating a user account | Policy |
| `roles.view` | `/admin/roles` list | `can:roles.view` |
| `roles.manage` | Create / edit / delete / clone roles | `can:roles.manage` |

## System

| Permission | Protects | Enforcement |
| --- | --- | --- |
| `system.settings` | `/admin/settings` | `can:system.settings` |
| `system.auditLog` | `/admin/audit-log` + show page | `can:system.auditLog` |
| `system.integrations` | Third-party integrations (Anthropic, etc.) | `can:system.integrations` |

---

## Role templates

Seeded by:

- `QuizAuthorRoleSeeder` — quiz + question bank CRUD, invites, results read
- `ReviewerRoleSeeder` — RLHF + coding review permissions
- `ProctorRoleSeeder` — results with suspicious-event + snapshot visibility
- `AuditorRoleSeeder` — audit log + read-only results access

Super admins bypass all checks. Users can hold multiple roles; permissions
are the union.
