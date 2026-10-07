# Edge-case path trace: integrator PRD

Scope: the whole of `prd.md` (addendum read for context). Method: lens-edge-case-hunter, path enumeration over the PRD's state machines and rules. Deletion check and claims check skipped (no diff, no claims file). Line numbers refer to `prd.md`. Only unhandled paths are listed.

```json
[
  {
    "location": "prd.md:159-164 (FR-1)",
    "trigger_condition": "Sign-up uses an email or phone already registered, pending, or failing verification repeatedly",
    "guard_snippet": "unique(verified_contact); on duplicate route to login or resume pending verification; expire codes after N minutes; cap attempts and resends per contact",
    "potential_consequence": "Duplicate Owners, hijacked pending sign-ups, or unbounded code guessing and message cost"
  },
  {
    "location": "prd.md:172-177 (FR-3), 179-184 (FR-4)",
    "trigger_condition": "Same NIN submitted by a second Business Account",
    "guard_snippet": "unique index on keyed hash of NIN; a duplicate goes to the Admin manual queue flagged as duplicate",
    "potential_consequence": "One person operates several Business Accounts, multiplying Vouch Code capacity and diluting identity checks"
  },
  {
    "location": "prd.md:179-184 (FR-4)",
    "trigger_condition": "Identity provider times out or errors during the single automatic NIN attempt",
    "guard_snippet": "if provider_result in (timeout,5xx) keep the attempt unused and retry with backoff; consume the attempt only on a definitive mismatch",
    "potential_consequence": "Provider outage permanently forces manual NIN review for that account"
  },
  {
    "location": "prd.md:179-184 (FR-4)",
    "trigger_condition": "Admin rejects a manual NIN verification",
    "guard_snippet": "status Rejected with reason shown to Owner; allow resubmission after edit, capped at N and logged",
    "potential_consequence": "Owner stuck at Level 1 with no reason, resubmission path, or appeal"
  },
  {
    "location": "prd.md:172-177 (FR-3), 226-244 (FR-11)",
    "trigger_condition": "Owner edits verified name or NIN, or ownership moves to an unverified User",
    "guard_snippet": "on NIN or name change or Change Owner require NIN verification of the incoming Owner before the transfer completes; log both",
    "potential_consequence": "Level 3 account controlled by a person with no NIN on record"
  },
  {
    "location": "prd.md:219-224 (FR-10), 226-244 (FR-11)",
    "trigger_condition": "Manager removes the Owner, since the Owner is a User",
    "guard_snippet": "if target.role == Owner reject removal; the Owner changes only through the Owner-initiated Change Owner flow",
    "potential_consequence": "Business Account left without its exactly-one Owner; Owner-only actions become unreachable"
  },
  {
    "location": "prd.md:226-244 (FR-11), 611-627 (FR-52)",
    "trigger_condition": "Owner has left the business or lost their only verified contact",
    "guard_snippet": "Admin-assisted Change Owner: Manager request, NIN re-verification of the new Owner, audit entry; contact-recovery flow for the Owner",
    "potential_consequence": "Account permanently stuck with no Owner-level actions and no recovery route"
  },
  {
    "location": "prd.md:226-244 (FR-11)",
    "trigger_condition": "Owner closes the Business Account with open Orders, Credit Notes, or Return Requests",
    "guard_snippet": "block close while any Order is not in (Completed,Expired,Cancelled), any Return Request is open, or any Credit Note is unexpired; require refund or application first",
    "potential_consequence": "Paid money, Credit Notes, and device returns orphaned with no account to resolve"
  },
  {
    "location": "prd.md:629-633 (FR-53), 741 (NFR-3)",
    "trigger_condition": "A closed account or removed User requests personal-data erasure",
    "guard_snippet": "audit rows reference user_id only; erase or pseudonymise profile and NIN on request; keep immutable audit rows free of direct identifiers",
    "potential_consequence": "Erasure duty collides with immutable audit entries; retention and pseudonymisation rules undefined"
  },
  {
    "location": "prd.md:219-224 (FR-10), 379-387 (FR-28)",
    "trigger_condition": "User is removed or demoted while holding an active session or unsynced offline edits",
    "guard_snippet": "on removal or role change revoke tokens, re-check role on every sync request, wipe the local cache at next contact, quarantine queued edits for Manager review",
    "potential_consequence": "Removed User keeps cached prices or later syncs edits under revoked authority"
  },
  {
    "location": "prd.md:226-244 (FR-11), 379-387 (FR-28)",
    "trigger_condition": "Technician signs in on a device whose offline cache holds a Manager's prices",
    "guard_snippet": "partition the local store by user_id and role; purge price fields on logout or role change; never cache price data for Technician sessions",
    "potential_consequence": "Technician can read cached wholesale prices offline, defeating server-side role checks"
  },
  {
    "location": "prd.md:219-224 (FR-10)",
    "trigger_condition": "Invitee's verified contact differs from the invite or already belongs to another account",
    "guard_snippet": "bind the invite token to the invited contact; expire after N days and allow revoke; one Business Account per verified contact, else an explicit transfer flow",
    "potential_consequence": "Wrong person joins, or membership across two accounts is undefined"
  },
  {
    "location": "prd.md:200-217 (FR-7 to FR-9), 611-627 (FR-52)",
    "trigger_condition": "Admin finds an approved Level 3 account is a non-integrator or fraudulent",
    "guard_snippet": "add Admin action Suspend or Downgrade Business Account: block new Orders and codes, define handling of open Orders, Credit Notes, Return Requests; audit",
    "potential_consequence": "Wholesale access cannot be withdrawn; only vouching rights can be suspended"
  },
  {
    "location": "prd.md:186-198 (FR-5, FR-6), 666-682 (FR-68, FR-58)",
    "trigger_condition": "Two sessions race on a single-use code or the 2-active-code cap",
    "guard_snippet": "atomic consume: UPDATE code SET used_at=now() WHERE id=? AND used_at IS NULL AND expires_at>now(); lock the account row while counting active codes",
    "potential_consequence": "Single-use code accepted twice, or a third active code created"
  },
  {
    "location": "prd.md:194-217 (FR-6 to FR-9)",
    "trigger_condition": "Voucher is suspended after the code is submitted but before Admin decides",
    "guard_snippet": "show voucher standing in the Admin queue; on suspension flag pending requests that used its codes; require an explicit Admin decision",
    "potential_consequence": "Request vouched by a suspended abuser is approved with no warning"
  },
  {
    "location": "prd.md:110 (Established Integrator), 186-192 (FR-5)",
    "trigger_condition": "Delivered Orders later refunded, fully returned, or zero-value count toward the threshold of 3",
    "guard_snippet": "established = count(Delivered Orders with net_paid_after_returns >= min_value) >= 3, re-evaluated at code generation and at submission",
    "potential_consequence": "Account becomes Established through trivial or reversed Orders and vouches others"
  },
  {
    "location": "prd.md:207-211 (FR-8)",
    "trigger_condition": "Admin creates a Level 3 account directly with no Owner contact verification or NIN",
    "guard_snippet": "created account is Pending Activation: Owner verifies contact and completes the NIN check before Level 3 features unlock; trade name required",
    "potential_consequence": "Level 3 account exists with unverified Owner, no NIN, and no trade name for Proposals"
  },
  {
    "location": "prd.md:611-627 (FR-52)",
    "trigger_condition": "Staff member leaves, changes role, or the last Admin is deactivated",
    "guard_snippet": "add staff deactivate and role-change actions: block removing the last Admin; reassign queue items held by that reviewer; revoke sessions; audit",
    "potential_consequence": "Orphaned queue items, retained access for leavers, or a platform with no Admin"
  },
  {
    "location": "prd.md:252-257 (FR-12), 684-689 (FR-59), 711-727 (FR-63 to FR-65)",
    "trigger_condition": "Warranty Period, serialised flag, DOA window, and exclusion flags have no Product maintenance requirement",
    "guard_snippet": "extend FR-12 attributes: warranty_months, serialised, doa_window_days, special_order, end_of_life, refurbished, software; required before publish",
    "potential_consequence": "Returns and warranty rules cannot be evaluated; exclusions and clocks silently default"
  },
  {
    "location": "prd.md:259-279 (FR-13 to FR-15)",
    "trigger_condition": "Operations bulk upload row carries Platform Markup or Active Supplier values",
    "guard_snippet": "per-column permission check by uploader role; reject or ignore Admin-only columns for Operations and report per row",
    "potential_consequence": "Operations bypasses Admin-only markup and supplier controls through spreadsheet import"
  },
  {
    "location": "prd.md:274-279 (FR-15)",
    "trigger_condition": "Row matches no identifier, several Products, or repeats within the same file",
    "guard_snippet": "require a unique SKU key per row; reject duplicate keys within the file; a missing or ambiguous key becomes a failed row",
    "potential_consequence": "Duplicate Products created or the wrong Product overwritten during upload"
  },
  {
    "location": "prd.md:252-257 (FR-12), 274-279 (FR-15), 294-298 (FR-18)",
    "trigger_condition": "Operations sets absolute stock quantity while Paid Orders hold reserved units",
    "guard_snippet": "store on_hand and reserved separately; available = on_hand - reserved; reject on_hand edits below reserved without explicit override",
    "potential_consequence": "Reserved units are oversold or silently released after a manual or bulk stock edit"
  },
  {
    "location": "prd.md:267-272 (FR-14)",
    "trigger_condition": "Admin switches to a Supplier with no cost entered, or deactivates the Active Supplier",
    "guard_snippet": "reject the switch when target supplier_cost is null or <= 0; block removing the Active Supplier without a replacement",
    "potential_consequence": "Wholesale Price becomes undefined or zero and the Product sells below cost"
  },
  {
    "location": "prd.md:313-319 (FR-20)",
    "trigger_condition": "Finance enters a rate of zero, negative, or wildly different from the current rate",
    "guard_snippet": "reject rate <= 0; if abs(new-old)/old > threshold require a second approver before activation; show a price-impact preview",
    "potential_consequence": "Mistyped rate reprices the whole Catalogue instantly and Orders lock at wrong prices"
  },
  {
    "location": "prd.md:259-265 (FR-13), 287-292 (FR-17), 306-311 (FR-19), 395-400 (FR-29)",
    "trigger_condition": "Margin, markup, discount, VAT, or quantity entered as negative, zero, or above 100 percent",
    "guard_snippet": "server-side bounds: 0 <= discount < 100, markup >= 0, VAT 0..100, margin >= 0 or explicit confirm, quantity >= 1",
    "potential_consequence": "Negative or oversized values corrupt prices, totals, and client-facing Proposals"
  },
  {
    "location": "prd.md:259-265 (FR-13), 287-292 (FR-17), 321-334 (FR-21), 342-346 (FR-23)",
    "trigger_condition": "Tier or Back-order Discount exceeds a Product's reduced Platform Markup",
    "guard_snippet": "price_ex_vat = max(wholesale*(1-disc), supplier_cost*(1+min_margin)); warn the editor when disc >= markup",
    "potential_consequence": "Selling Price falls below Supplier Cost, giving negative margin on the platform's only revenue"
  },
  {
    "location": "prd.md:281-285 (FR-16), 367-371 (FR-26), 453-458 (FR-36)",
    "trigger_condition": "Requested quantity exceeds on-hand stock or crosses the Stock Threshold",
    "guard_snippet": "available = stock - threshold; if qty > available split the line, send the remainder as a Back-order Item if enabled, else cap or block with a message",
    "potential_consequence": "Order accepts units that cannot ship, or silently mixes stock and back-order lines"
  },
  {
    "location": "prd.md:294-298 (FR-18)",
    "trigger_condition": "Two Orders reach confirmation together competing for the last available unit",
    "guard_snippet": "reserve atomically: UPDATE product SET reserved=reserved+n WHERE on_hand-reserved>=n; credit the line when zero rows are updated",
    "potential_consequence": "Both Orders become Paid and stock goes negative, overselling the unit"
  },
  {
    "location": "prd.md:294-298 (FR-18), 517-523 (FR-43)",
    "trigger_condition": "Stock covers only part of an Order Line's quantity at confirmation",
    "guard_snippet": "reserve min(qty, available); credit only the shortfall quantity unless the Integrator opted for whole-line; recompute Shipments",
    "potential_consequence": "Whole line credited needlessly, or the partial quantity ships without Integrator consent"
  },
  {
    "location": "prd.md:287-292 (FR-17), 453-458 (FR-36)",
    "trigger_condition": "Operations changes a Back-order Discount, Lead Time, or setting after opt-in",
    "guard_snippet": "snapshot discount and lead_time on the Order Line at creation; if back-order is disabled before Paid, credit the line with notice",
    "potential_consequence": "Integrator pays on terms they did not accept, or line becomes unorderable unnoticed"
  },
  {
    "location": "prd.md:287-298 (FR-17, FR-18), 477-481 (FR-39)",
    "trigger_condition": "Inbound back-order stock arrives short of total quantity owed to Paid Orders",
    "guard_snippet": "allocate inbound units to Paid back-order lines FIFO by Paid timestamp; partial allocation creates a partial Shipment; notify the rest",
    "potential_consequence": "Arbitrary or contested allocation across Integrators; some Orders wait with no rule"
  },
  {
    "location": "prd.md:287-292 (FR-17), 483-488 (FR-57), 517-523 (FR-43)",
    "trigger_condition": "Back-order Item passes its Lead Time or the Supplier cannot supply it",
    "guard_snippet": "overdue state at lead_time+grace: notify Integrator, offer cancel with Credit Note; Operations marks Supplier-unable to cancel and credit the line",
    "potential_consequence": "Integrator locked into an indefinitely late Order with no exit or remedy"
  },
  {
    "location": "prd.md:531-536 (FR-44), 294-298 (FR-18)",
    "trigger_condition": "Operations' stock check at approval finds a Paid Order Line physically unavailable",
    "guard_snippet": "add Operations action Credit Line: set line Cancelled, release reservation, Credit Note via Finance, notify Integrator; Approve proceeds on the remaining lines",
    "potential_consequence": "Paid Order cannot be approved, cancelled by platform, or credited; stuck indefinitely"
  },
  {
    "location": "prd.md:437-443 (FR-34), 367-371 (FR-26), 431-435 (FR-33), 729-735 (FR-66)",
    "trigger_condition": "Project or Cart line is no longer orderable at Order creation",
    "guard_snippet": "on create validate each line; if any is non-orderable block with the list and require removal or replacement; never drop silently",
    "potential_consequence": "Whole-Project rule and availability rule conflict; Order blocked or lines dropped silently"
  },
  {
    "location": "prd.md:306-311 (FR-19), 402-409 (FR-30), 742 (NFR-4)",
    "trigger_condition": "Line sums differ from the total after per-line half-up rounding",
    "guard_snippet": "round once per line in NGN half-up; total = sum of rounded lines; VAT line = total - sum(ex-VAT lines); never recompute VAT on the grand total",
    "potential_consequence": "Grand total changes when the VAT line toggles, contradicting the stated invariant"
  },
  {
    "location": "prd.md:324-329 (FR-21 tier table)",
    "trigger_condition": "Spend of 4,999.50 or 11,999.99 USD falls between published tier bands",
    "guard_snippet": "half-open bands: spend < 1500 Base; < 5000 Tier 2; < 12000 Tier 3; >= 12000 Tier 4",
    "potential_consequence": "Fractional spend maps to no tier, causing wrong or undefined discount"
  },
  {
    "location": "prd.md:321-334 (FR-21)",
    "trigger_condition": "Delivered spend counted with VAT or delivery fees, at an unspecified conversion rate",
    "guard_snippet": "spend = sum(order.goods_total_ex_vat_ngn / order.platform_rate) over Orders completed in the trailing six months, excluding delivery and VAT",
    "potential_consequence": "Tier outcomes vary by implementation and drift with exchange rate despite USD intent"
  },
  {
    "location": "prd.md:321-334 (FR-21), 483-488 (FR-57), 698-703 (FR-61)",
    "trigger_condition": "Order Lines returned or credited after delivery still count in Delivered spend",
    "guard_snippet": "spend = delivered_value - approved return credits and refunds in the credit window; Credit-Note-funded Orders count only their cash portion",
    "potential_consequence": "Tier inflated by goods the Integrator returned or paid for with credit"
  },
  {
    "location": "prd.md:336-340 (FR-22), 348-352 (FR-24)",
    "trigger_condition": "Order Delivered or created between tier calculation and the displayed effective date",
    "guard_snippet": "snapshot spend at calculation date S; apply the tier from effective date E; Orders Delivered after S count next cycle; Order uses the tier effective at created_at",
    "potential_consequence": "Previewed tier differs from applied tier, or Order priced at an ambiguous tier"
  },
  {
    "location": "prd.md:379-387 (FR-28)",
    "trigger_condition": "Two devices edit different Rooms of one Project offline, or device clocks disagree",
    "guard_snippet": "version Projects on the server; merge per Room or item using a server-assigned sequence; whole-Project overwrite only on same-item conflict; keep the prior version",
    "potential_consequence": "Whole-Project overwrite drops non-overlapping edits; skewed clock picks the wrong winner"
  },
  {
    "location": "prd.md:367-371 (FR-26), 379-387 (FR-28), 729-735 (FR-66)",
    "trigger_condition": "Offline edit adds a Product later Out of Stock without back-order or counterfeit-flagged",
    "guard_snippet": "at sync validate each item against the live Catalogue; keep the item flagged Unavailable with a warning; never discard the edit silently",
    "potential_consequence": "Offline edit fails FR-26 or FR-66 rules at sync with undefined outcome"
  },
  {
    "location": "prd.md:360-371 (FR-25, FR-26), 395-400 (FR-29), 437-443 (FR-34)",
    "trigger_condition": "Proposal or Order requested for a Project or Cart with no items or zero quantities",
    "guard_snippet": "require >= 1 Project Item with qty >= 1 (integer unless the Product has a unit of measure) before Proposal or Order creation",
    "potential_consequence": "Empty zero-value PDFs and Orders get created and may count as Delivered Orders"
  },
  {
    "location": "prd.md:395-400 (FR-29), 411-417 (FR-31), 445-451 (FR-35)",
    "trigger_condition": "Project items or quantities change after the Proposal was generated, then Order is created",
    "guard_snippet": "store project_revision on the Proposal; if the current revision differs treat the Proposal as stale: use the current rate or require regenerate",
    "potential_consequence": "Proposal Rate protection and difference display apply to goods the Proposal never quoted"
  },
  {
    "location": "prd.md:395-400 (FR-29), 419-423 (FR-32)",
    "trigger_condition": "Order created on the expiry date after generation time, or generation on the 31st",
    "guard_snippet": "expires_at = end of day WAT of generated_at + 1 calendar month clamped to month end; PDF shows that date; compare using server time",
    "potential_consequence": "Proposal shows a valid date but Order uses current rate; month-end dates miscomputed"
  },
  {
    "location": "prd.md:402-409 (FR-30)",
    "trigger_condition": "Business name or logo is missing, including Admin-created accounts without a trade name",
    "guard_snippet": "require trade_name before the first Proposal; add a logo upload and edit requirement; fall back to a name-only header when the logo is absent",
    "potential_consequence": "Proposal PDF generation fails or prints a blank header; no requirement to upload logos"
  },
  {
    "location": "prd.md:287-292 (FR-17), 402-409 (FR-30)",
    "trigger_condition": "Proposal PDF generated for a Project containing a Back-order Item",
    "guard_snippet": "print per-line availability and Lead Time for Back-order Items; keep Back-order Discount off the PDF",
    "potential_consequence": "Client expects immediate delivery of back-ordered items quoted without Lead Time"
  },
  {
    "location": "prd.md:431-443 (FR-33, FR-34), 647-651 (FR-55)",
    "trigger_condition": "Order-create retried, double-submitted, or run from one shared Cart by two Users",
    "guard_snippet": "idempotency_key on create-order; consume the Cart in the same transaction; define Cart scope per User or per Account",
    "potential_consequence": "Duplicate Orders created, since repeat Orders from one Project are permitted"
  },
  {
    "location": "prd.md:445-451 (FR-35), 313-319 (FR-20), 348-352 (FR-24), 453-458 (FR-36)",
    "trigger_condition": "Platform Rate, tier, or Proposal validity changes between difference display and Order confirm",
    "guard_snippet": "issue quote_id binding rate_version, tier, proposal_id, project_revision, back-order terms; on confirm recompute server-side and reject with the re-shown difference if any input changed",
    "potential_consequence": "Order created at a price the Integrator never saw or accepted"
  },
  {
    "location": "prd.md:445-451 (FR-35), 467-475 (FR-38)",
    "trigger_condition": "Late payment reprices an Order that used the Proposal Rate within NGN 5 tolerance",
    "guard_snippet": "reprice through the same FR-35 function: if proposal_valid and abs(current-proposal_rate) <= 5 keep proposal_rate else use current",
    "potential_consequence": "Integrator loses Proposal protection solely because payment landed after 24 hours"
  },
  {
    "location": "prd.md:467-475 (FR-38), 313-319 (FR-20)",
    "trigger_condition": "Order reprices with partial confirmed payments while the rate can move again",
    "guard_snippet": "reprice once at a fixed instant stamping rate_id; balance = new_total - confirmed_ngn; negative balance becomes a Credit Note; keep VAT, markup, tier as at creation; freeze until terminal; notify",
    "potential_consequence": "Balance owed and rate-down overpayment undefined; displayed amount may differ at confirmation"
  },
  {
    "location": "prd.md:467-475 (FR-38)",
    "trigger_condition": "Hour 24 passes while an in-time Receipt still awaits Finance review",
    "guard_snippet": "keep Amount Due at the creation price while any in-time Receipt is Pending; reprice only if the lock test fails after review",
    "potential_consequence": "Amount Due flips between repriced and locked values; Integrator pays the wrong amount"
  },
  {
    "location": "prd.md:467-475 (FR-38), 647-651 (FR-55)",
    "trigger_condition": "Receipt upload starts before hour 24 or 48, completes after, or lands exactly on it",
    "guard_snippet": "classify by server-recorded upload-session start; lock if t < created+24h; reprice if created+24h <= t < created+48h",
    "potential_consequence": "Lock or expiry decided by device clock, retry time, or ambiguous boundary"
  },
  {
    "location": "prd.md:467-475 (FR-38), 502-508 (FR-41)",
    "trigger_condition": "Receipt upload stops expiry, yet every Receipt is Unconfirmed or the balance never arrives",
    "guard_snippet": "on Unconfirmed or shortfall restart a final N-hour grace from the decision time, then expire or cancel with a Credit Note for any confirmed amount",
    "potential_consequence": "Order stays Awaiting Payment forever; it never expires and holds partial money"
  },
  {
    "location": "prd.md:467-475 (FR-38)",
    "trigger_condition": "Payment uploaded after hour 48 on an Order kept alive by an earlier Receipt",
    "guard_snippet": "define band t >= created+48h: reprice at the current rate, or reject and move the Order to Expired with a Credit Note for confirmed amounts",
    "potential_consequence": "Payment falls outside the lock, reprice, and expiry bands with no rule"
  },
  {
    "location": "prd.md:467-475 (FR-38), 502-508 (FR-41)",
    "trigger_condition": "In-time Receipt is marked Unconfirmed after hour 24 for a fixable reason",
    "guard_snippet": "a re-upload within N hours of the Unconfirmed notice inherits the original upload timestamp when the full amount matches",
    "potential_consequence": "Integrator loses Price Lock due to Finance review delay, since re-upload is necessarily late"
  },
  {
    "location": "prd.md:467-475 (FR-38), 510-515 (FR-42)",
    "trigger_condition": "Transfer is short by kobo-level rounding or bank charges against the Amount Due",
    "guard_snippet": "display Amount Due in whole naira; a Finance-set underpayment tolerance of NGN X counts as covered, written off and audited",
    "potential_consequence": "Trivial shortfall forfeits Price Lock and leaves Order Awaiting Payment"
  },
  {
    "location": "prd.md:459-465 (FR-37), 551-556 (FR-47)",
    "trigger_condition": "Entered delivery address cannot be resolved to a Zone or its Zone Rate is unset",
    "guard_snippet": "if zone is null or rate is null block order creation and offer a manual zone pick or Operations quote; every active zone must have a rate",
    "potential_consequence": "Amount Due cannot be computed; Order created with zero or wrong delivery cost"
  },
  {
    "location": "prd.md:459-465 (FR-37), 477-481 (FR-39)",
    "trigger_condition": "Integrator needs to change address, Store, Pick-up or Delivery, or parts choice after creation",
    "guard_snippet": "allow edit while Awaiting Payment (recalculate Amount Due, re-evaluate Price Lock) or Paid before Approval via Operations request; lock afterwards",
    "potential_consequence": "No edit path; wrong address or Store forces cancellation, or Amount Due drifts"
  },
  {
    "location": "prd.md:459-465 (FR-37), 477-481 (FR-39), 531-536 (FR-44), 517-523 (FR-43)",
    "trigger_condition": "Stock-out credit or back-order partial arrival changes Shipment count after delivery cost was charged",
    "guard_snippet": "fix Shipments at creation; on line credit return the freed Shipment's delivery cost; extra Shipments are Operations-absorbed unless the Integrator requests a split",
    "potential_consequence": "Delivery fees over-collected for removed Shipments or never collected for extra ones"
  },
  {
    "location": "prd.md:483-488 (FR-57)",
    "trigger_condition": "Awaiting Payment Order with confirmed or pending Payments is cancelled at no cost",
    "guard_snippet": "on cancel issue a Credit Note for sum(confirmed payments); route pending Payments to Finance review; clear Amount Due only after reconciliation",
    "potential_consequence": "Money already confirmed is kept with no Credit Note on cancellation"
  },
  {
    "location": "prd.md:483-488 (FR-57), 531-536 (FR-44), 510-515 (FR-42), 467-475 (FR-38)",
    "trigger_condition": "Cancel request races Approve, final payment confirmation, or the expiry job",
    "guard_snippet": "transition via UPDATE orders SET status=? WHERE id=? AND status=?; the losing request gets the current status and Approved reroutes to consent",
    "potential_consequence": "Cancelled Order approved or shipped, or paid Order cancelled without Credit Note"
  },
  {
    "location": "prd.md:483-488 (FR-57)",
    "trigger_condition": "Cancellation of an Approved Order awaits consent while Shipments keep progressing",
    "guard_snippet": "add a Cancellation Requested state: freeze Shipment transitions until Operations decides; define SLA, rejection reason, and re-request rules",
    "potential_consequence": "Order ships mid-request; consent outcome, reserved stock, and refund path undefined"
  },
  {
    "location": "prd.md:483-488 (FR-57)",
    "trigger_condition": "Approved Order mixes cancellable lines with a Back-order Item already ordered from Supplier",
    "guard_snippet": "add Order Line status Cancelled and per-line cancel with a line Credit Note; Operations sets supplier_ordered_at; Order is Cancelled only when all lines are",
    "potential_consequence": "No partial-cancel state; the supplier-ordered flag has no owner or record"
  },
  {
    "location": "prd.md:483-488 (FR-57), 145 (Return Request)",
    "trigger_condition": "Some Shipments progress while others are unshipped, or goods are In-transit or Ready for Pickup",
    "guard_snippet": "evaluate cancel per Shipment: unshipped Shipments cancellable with consent and Credit Note; undelivered goods use a refusal-on-delivery or uncollected-pickup flow",
    "potential_consequence": "Unshipped or undelivered goods fall under neither cancellation nor returns rules"
  },
  {
    "location": "prd.md:496-508 (FR-40, FR-41), 483-488 (FR-57)",
    "trigger_condition": "Receipt or transfer arrives for an Expired, Cancelled, or already Paid Order",
    "guard_snippet": "accept the upload into an Unmatched queue linked to the Order; Finance resolves with Credit Note or refund; terminal Orders never auto-transition",
    "potential_consequence": "Customer money received with no status, claim path, or Credit Note rule"
  },
  {
    "location": "prd.md:496-508 (FR-40, FR-41)",
    "trigger_condition": "Same bank transfer evidenced by two Receipts, or one transfer covering two Orders",
    "guard_snippet": "capture transfer reference and amount per Payment; unique(reference); Finance can split one transfer across Orders with allocation records",
    "potential_consequence": "Order reaches Paid on double-counted money, or valid combined transfers are rejected"
  },
  {
    "location": "prd.md:496-500 (FR-40), 647-651 (FR-55)",
    "trigger_condition": "Receipt file is oversized, unsupported, corrupt, blank, or unrelated to the Order",
    "guard_snippet": "allow jpg, png, pdf up to N MB; reject corrupt or zero-byte files; scan for malware; a junk upload does not stop expiry until Finance accepts it as a Receipt",
    "potential_consequence": "Junk or malicious upload stops expiry and wastes Finance review time"
  },
  {
    "location": "prd.md:502-515 (FR-41, FR-42)",
    "trigger_condition": "Finance reverses a Confirmed Payment after Order is Paid, or two reviewers decide simultaneously",
    "guard_snippet": "set payment.outcome by compare-and-set from Pending; add a Voided state with reason and audit; a Paid Order with a voided payment returns to Awaiting Payment before Approval",
    "potential_consequence": "Order stays Paid, reserved, or shipped though the transfer never settled"
  },
  {
    "location": "prd.md:510-515 (FR-42), 467-475 (FR-38), 517-523 (FR-43)",
    "trigger_condition": "Finance applies a Credit Note toward an Order's Amount Due",
    "guard_snippet": "model credit application as Payment type Credit with applied_at = Integrator request time; count it toward Paid, the lock test, and the expiry stop",
    "potential_consequence": "Credit-funded Order never reaches Paid, loses lock, or expires despite being covered"
  },
  {
    "location": "prd.md:517-523 (FR-43), 483-488 (FR-57)",
    "trigger_condition": "Order funded partly by an applied Credit Note is cancelled or expires",
    "guard_snippet": "restore the applied amount to the original Credit Note with its original expiry, or issue a new note with a fresh 12 months; audit",
    "potential_consequence": "Applied credit lost, double-restored, or restored already expired"
  },
  {
    "location": "prd.md:517-523 (FR-43)",
    "trigger_condition": "Integrator requests applying a Credit Note before expiry; Finance acts after expiry",
    "guard_snippet": "evaluate validity at request created_at; Finance may complete after expiry; show expiry as end of day WAT",
    "potential_consequence": "Credit lapses through Finance latency, not Integrator inaction, causing disputed forfeiture"
  },
  {
    "location": "prd.md:517-523 (FR-43)",
    "trigger_condition": "Credit Note expires unused after 12 months",
    "guard_snippet": "notify at T-60 and T-14 days; on expiry define disposition (refund on request or forfeit per signed terms) and write a ledger entry",
    "potential_consequence": "Customer money retained by the platform with no disposition, notice, or refund rule"
  },
  {
    "location": "prd.md:517-523 (FR-43)",
    "trigger_condition": "Apply and refund requested for the same Credit Note, or amounts partly consumed",
    "guard_snippet": "credit_note(status,balance); apply or refund decrements balance in one transaction where balance >= amount; states Issued, Partially Used, Used, Refunded, Expired; eligible target Orders defined",
    "potential_consequence": "Credit double-spent, over-applied past its balance, or applied to ineligible Orders"
  },
  {
    "location": "prd.md:517-523 (FR-43)",
    "trigger_condition": "Refund due but transfers came from third parties, several accounts, or a closed account",
    "guard_snippet": "record the payer account on each confirmed Payment; refund pro-rata to source; if invalid Finance verifies an alternative account in the Owner's name",
    "potential_consequence": "Refund fails, is misdirected, or stalls with no defined alternative destination"
  },
  {
    "location": "prd.md:143 (Credit Note), 517-523 (FR-43), 705-710 (FR-62), 729-735 (FR-66)",
    "trigger_condition": "Finance credits freight or counterfeit losses, reasons absent from Credit Note issuance rules",
    "guard_snippet": "extend the Credit Note reason enum: overpayment, unfulfilled line, cancelled Order, approved return, freight reimbursement, counterfeit credit; each with approver and amount source",
    "potential_consequence": "Credit Notes issued for unlisted reasons lack defined authority, amount source, or audit"
  },
  {
    "location": "prd.md:537-544 (FR-45)",
    "trigger_condition": "Operations sets a Shipment status out of sequence or marks Delivered by mistake",
    "guard_snippet": "allow only Processing>In-transit>Delivered and Processing>Ready for Pickup>Signed For; reversals need an Admin reason and audit; require courier name and tracking at In-transit",
    "potential_consequence": "Wrong Delivered timestamp starts return windows, warranty, and tier spend incorrectly"
  },
  {
    "location": "prd.md:537-544 (FR-45)",
    "trigger_condition": "Courier cannot deliver, recipient refuses, or the parcel is lost while In-transit",
    "guard_snippet": "add Shipment statuses Delivery Failed, Returned to Store, Lost with an Operations reason; Lost triggers the non-delivery refund; Failed returns to Processing",
    "potential_consequence": "Shipment stuck In-transit forever; Order never Completes and no remedy applies"
  },
  {
    "location": "prd.md:537-544 (FR-45), 558-567 (FR-56)",
    "trigger_condition": "Pick-up Shipment stays Ready for Pickup and is never collected",
    "guard_snippet": "notify at N days; at M days Operations may cancel the Shipment, restock, and issue a Credit Note less handling per terms",
    "potential_consequence": "Order never Completes, stock stays held indefinitely, warranty clock runs unnoticed"
  },
  {
    "location": "prd.md:537-544 (FR-45), 149 (Delivered Order)",
    "trigger_condition": "Back-order Order Line has no Shipment yet when other Shipments reach Delivered",
    "guard_snippet": "Completed iff every non-credited Order Line is in a Delivered or Signed For Shipment and at least one such Shipment exists",
    "potential_consequence": "Order flips Completed early or vacuously, counting toward tier spend and vouching"
  },
  {
    "location": "prd.md:294-298 (FR-18), 517-523 (FR-43), 537-544 (FR-45)",
    "trigger_condition": "Every Order Line is Credit-Noted at confirmation, leaving a Paid Order with zero Shipments",
    "guard_snippet": "if all lines are credited set the Order Cancelled with a Credit Note for the full confirmed amount; never evaluate Completed over an empty Shipment set",
    "potential_consequence": "Empty Order sits Paid or counts as Delivered, inflating spend and vouching eligibility"
  },
  {
    "location": "prd.md:558-567 (FR-56)",
    "trigger_condition": "Store is deactivated while open Pick-up Orders reference it, or it is the Main Store",
    "guard_snippet": "block deactivation while open Shipments reference the Store unless Operations reassigns them with Integrator notice; require a replacement Main Store first",
    "potential_consequence": "Pick-up Orders point to a closed Store; Delivery has no default dispatch Store"
  },
  {
    "location": "prd.md:723-727 (FR-65), 659-664 (FR-67)",
    "trigger_condition": "Serial count differs from quantity, repeats across Shipments (including restocked returns), or is mistyped",
    "guard_snippet": "require count(serials) == shipped qty; unique serial per active dispatch; normalise case and separators; Admin correction action with audit",
    "potential_consequence": "Lookup returns multiple or no match for a genuine device, blocking its Issue Code"
  },
  {
    "location": "prd.md:575-579 (FR-48), 587-591 (FR-50)",
    "trigger_condition": "Operations replaces or removes Documentation a User already saved offline",
    "guard_snippet": "version Documentation; on sync mark saved copies Superseded or Withdrawn, refresh when online, and show the version date offline",
    "potential_consequence": "Technician installs from a stale or withdrawn manual with no warning"
  },
  {
    "location": "prd.md:599-603 (FR-51), 675-682 (FR-58)",
    "trigger_condition": "Technician raises a Return Request awaiting Manager approval inside a 48-hour window",
    "guard_snippet": "add event Return Request awaiting approval to the approver's WhatsApp and email, with a reminder at half the Issue Code window",
    "potential_consequence": "Approver unaware; the 48-hour clock expires before anyone approves"
  },
  {
    "location": "prd.md:599-603 (FR-51), 467-475 (FR-38)",
    "trigger_condition": "Price Lock cutoff approaches or an Order reprices after hour 24",
    "guard_snippet": "events: lock deadline warning at hour 20, reprice notice with new Amount Due and deadline, expiry warning at hour 40",
    "potential_consequence": "Integrator loses the lock or pays a stale amount without being warned"
  },
  {
    "location": "prd.md:599-603 (FR-51), 517-523 (FR-43)",
    "trigger_condition": "Credit Note approaches its 12-month expiry",
    "guard_snippet": "events: Credit Note issued, expiring in 60 days, expiring in 14 days, sent to Owner and Managers",
    "potential_consequence": "Integrator silently loses credit value; expiry disputes follow"
  },
  {
    "location": "prd.md:599-603 (FR-51), 241-244 (FR-11)",
    "trigger_condition": "Notification goes to a Technician, a removed User, or fails on WhatsApp or email",
    "guard_snippet": "route by role: amounts only to Owner and Manager; Technician gets amount-free text; fall back WhatsApp to email on failure; skip removed Users; log delivery",
    "potential_consequence": "Amounts leak to Technicians; critical deadlines are missed on silent delivery failure"
  },
  {
    "location": "prd.md:629-633 (FR-53)",
    "trigger_condition": "Staff change VAT rate, return windows, Zone Rates, stock, or Shipment status",
    "guard_snippet": "extend audited events: VAT rate, windows, Zone Rates, Stores, stock edits, Shipment and serial changes, cancellation consent, NIN access, Credit Note application",
    "potential_consequence": "Money-affecting changes outside the listed events leave no immutable who-did-what record"
  },
  {
    "location": "prd.md:675-682 (FR-58), 691-696 (FR-60)",
    "trigger_condition": "Manager or Owner declines a Technician-raised request instead of approving it",
    "guard_snippet": "Manager decline moves Raised to Rejected with a reason shown to the Technician; pending requests expire at Issue Code expiry with notice to both",
    "potential_consequence": "Decline actor, reason, and notice undefined; request waits invisibly until the code lapses"
  },
  {
    "location": "prd.md:675-682 (FR-58)",
    "trigger_condition": "Approver corrects the suggested Order Line to one not matching the Issue Code's serial",
    "guard_snippet": "the corrected line must contain the serial or Product bound to the code, belong to the same Business Account, and be delivered; otherwise reject the correction",
    "potential_consequence": "Issue Code binding bypassed; device credited against the wrong Order Line or price"
  },
  {
    "location": "prd.md:675-689 (FR-58, FR-59)",
    "trigger_condition": "Technician raises within the window but Manager approves after the window closes",
    "guard_snippet": "evaluate the window at the raise timestamp; store window_deadline on the request; approval after the deadline stays valid until Issue Code expiry",
    "potential_consequence": "Valid request refused or accepted inconsistently depending on which timestamp is checked"
  },
  {
    "location": "prd.md:684-689 (FR-59)",
    "trigger_condition": "Delivery on Saturday or recorded late by Operations; 2-day and 7-day Sunday rules differ",
    "guard_snippet": "window start = courier-confirmed delivery date entered by Operations; 2-day window skips Sundays only; 7-day counts calendar days; compute in WAT to end of day",
    "potential_consequence": "Return refused or accepted wrongly at window edges; Sunday treatment inconsistent"
  },
  {
    "location": "prd.md:666-673 (FR-68), 684-689 (FR-59)",
    "trigger_condition": "Return Request lapses and a new Issue Code is generated after the original window",
    "guard_snippet": "the new request carries original_raised_at; the window is checked against it; cap new codes per Order Line at N",
    "potential_consequence": "Re-raise refused as late, or unlimited code regeneration stretches every window"
  },
  {
    "location": "prd.md:666-673 (FR-68), 691-696 (FR-60)",
    "trigger_condition": "Device reaches a Store after Lapsed, or Support records arrival after hour 48",
    "guard_snippet": "lapse test uses the physical arrival time logged at the Store; hold the lapse job outside Store hours; late devices go to a Held state for Support triage",
    "potential_consequence": "Physically returned device has no request, owner, or disposition; Sunday closures shorten window"
  },
  {
    "location": "prd.md:666-682 (FR-68, FR-58), 698-703 (FR-61)",
    "trigger_condition": "Second Return Request on the same serial or Order Line, or quantity above units remaining",
    "guard_snippet": "one open request per serial; sum(returned qty) per Order Line <= delivered qty; serialised returns need one serial per unit; prorate credit per unit; a non-serialised code binds to the Order Line",
    "potential_consequence": "Order Line credited or replaced more times than units delivered"
  },
  {
    "location": "prd.md:675-696 (FR-58 to FR-60), 698-703 (FR-61)",
    "trigger_condition": "Investigation shows a different cause than the reason the Integrator selected",
    "guard_snippet": "Support may reclassify the reason with a note; re-evaluate window, exclusions, and remedy table on reclassification; Integrator sees the change",
    "potential_consequence": "Wrong window, remedy, or fee applies because the self-selected reason is final"
  },
  {
    "location": "prd.md:698-703 (FR-61), 717-721 (FR-64), 729-735 (FR-66)",
    "trigger_condition": "Warranty or transit-damage claims and counterfeit cases meet conflicting FR-61, FR-64, FR-66 remedy rules",
    "guard_snippet": "remedy matrix by reason: warranty = RMA only then Reject; counterfeit = refund; transit damage = replace then credit; RMA failure falls back to Credit Note or Reject",
    "potential_consequence": "Operations cannot tell whether warranty gets replacement or credit, or counterfeit gets refund or credit"
  },
  {
    "location": "prd.md:691-703 (FR-60, FR-61), 611-627 (FR-52)",
    "trigger_condition": "Decided remedy needs Finance to issue a Credit Note or refund, or RMA takes weeks",
    "guard_snippet": "add states Awaiting Finance and Awaiting RMA with owner and SLA; Resolved only when credit is issued, the replacement is Delivered, or the repaired unit is returned",
    "potential_consequence": "Return stays Decided indefinitely; Operations decides, but only Finance can pay"
  },
  {
    "location": "prd.md:691-696 (FR-60)",
    "trigger_condition": "Device tests fine or is Rejected and the Integrator never collects or pays return cost",
    "guard_snippet": "charge return freight as Amount Due or a Credit Note offset before dispatch; unclaimed after N days moves to Closed Unclaimed per signed terms",
    "potential_consequence": "No mechanism to collect the cost; device and request linger unresolved"
  },
  {
    "location": "prd.md:698-703 (FR-61), 717-721 (FR-64), 537-544 (FR-45)",
    "trigger_condition": "Replacement unit ships from stock against an already Completed Order",
    "guard_snippet": "model the replacement as a zero-value Replacement Shipment excluded from Delivered Order counts and spend; record the new serial; define warranty restart or inherit",
    "potential_consequence": "Order status reopens, or zero-value Orders inflate tier and vouching; warranty clock unclear"
  },
  {
    "location": "prd.md:717-721 (FR-64), 659-664 (FR-67)",
    "trigger_condition": "Pick-up Shipment has no dispatch event, or the Product records no Warranty Period",
    "guard_snippet": "warranty_start = In-transit or Ready for Pickup timestamp; a null Warranty Period means no warranty and warranty claims are refused with a reason",
    "potential_consequence": "Warranty expiry blank or computed from wrong date; claims accepted without coverage"
  },
  {
    "location": "prd.md:729-735 (FR-66), 698-703 (FR-61)",
    "trigger_condition": "Suspected counterfeit is later confirmed genuine or confirmed fake",
    "guard_snippet": "states Suspected, Cleared, Confirmed with who and when; Cleared releases quarantine; Confirmed enables the cash refund and Supplier notice",
    "potential_consequence": "Quarantine never lifts, and the confirmed-counterfeit refund condition has no status"
  },
  {
    "location": "prd.md:729-735 (FR-66)",
    "trigger_condition": "Product flagged counterfeit while in open Orders, Carts, valid Proposals, or unshipped lines",
    "guard_snippet": "on flag block payment confirmation and shipment of affected lines, credit Paid lines via Finance, remove from Carts, mark Proposals stale; define batch by serial range or Shipment",
    "potential_consequence": "Flagged goods can still be paid for, reserved, quoted, or shipped"
  },
  {
    "location": "prd.md:675-682 (FR-58), 698-703 (FR-61)",
    "trigger_condition": "Shipment marked Delivered but goods never arrived, so non-delivery refund is requested",
    "guard_snippet": "add reason Non-delivery to FR-58 with its own window from the Delivered timestamp, an Operations check against courier proof, and a Finance refund path",
    "potential_consequence": "FR-61 grants a non-delivery refund that no Return Request reason can reach"
  },
  {
    "location": "prd.md:684-689 (FR-59)",
    "trigger_condition": "Admin edits a return window while Return Requests are in flight",
    "guard_snippet": "snapshot window_days on the request at creation; changes apply only to requests raised afterwards, as FR-47 does for Zone Rates",
    "potential_consequence": "Window edit retroactively invalidates or revives pending requests"
  }
]
```
