# Updated Vercel AI Prompt - Vertical EPS/Revenue Layout

Create a modern, responsive web application for displaying financial earnings data in a clean table format with **vertical layout for EPS and Revenue columns**.

## Key Requirements:

### Table Structure:

1. **Company Column:**

   - Bold ticker symbol (e.g., "AAPL")
   - Light gray company name below (e.g., "Apple Inc.")

2. **Size Column:**

   - Display market cap size category (Mega, Large, Mid, Small)
   - Color-coded badges (Mega: blue, Large: green, Mid: orange, Small: gray)

3. **Market Cap Column:**

   - Main market cap value (e.g., "$2.5T")
   - Below: market cap change with color coding (green for positive, red for negative)

4. **Price Column:**

   - Current stock price (e.g., "$150.25")
   - Below: percentage change with color coding

5. **EPS Column (VERTICAL LAYOUT):**

   - **Actual EPS** value (top)
   - **Estimated EPS** value (middle)
   - **EPS Surprise** percentage (bottom, color-coded)

6. **Revenue Column (VERTICAL LAYOUT):**
   - **Actual Revenue** (top, e.g., "$89.5B")
   - **Estimated Revenue** (middle)
   - **Revenue Surprise** percentage (bottom, color-coded)

## Critical Design Change:

**EPS and Revenue columns must display data VERTICALLY (stacked) instead of horizontally:**

```
EPS Column:          Revenue Column:
┌─────────────┐     ┌─────────────┐
│ $1.52       │     │ $89.50B     │  ← Actual
├─────────────┤     ├─────────────┤
│ $1.48       │     │ $88.20B     │  ← Estimate
├─────────────┤     ├─────────────┤
│ +2.7%       │     │ +1.5%       │  ← Surprise
└─────────────┘     └─────────────┘
```

**NOT horizontally like:**

```
EPS: $1.52 | $1.48 | +2.7%
Revenue: $89.50B | $88.20B | +1.5%
```

## Technical Implementation:

- Use `space-y-1` or `flex flex-col` for vertical stacking
- Each value should be on its own line within the cell
- Maintain proper spacing and alignment
- Keep color coding for positive/negative surprises
- Ensure responsive design works with vertical layout

## Styling Guidelines:

- **Actual values**: Bold, dark text
- **Estimated values**: Regular, gray text
- **Surprise values**: Color-coded (green/red), bold
- **Vertical spacing**: Consistent spacing between stacked values
- **Cell height**: Adjust to accommodate 3 stacked values

## Example CSS Classes:

```css
.eps-cell,
.revenue-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem; /* space-y-1 equivalent */
}

.actual-value {
  font-weight: 600;
  color: #111827; /* dark text */
}

.estimate-value {
  color: #6b7280; /* gray text */
}

.surprise-value {
  font-weight: 600;
  color: #059669; /* green for positive */
}

.surprise-value.negative {
  color: #dc2626; /* red for negative */
}
```

Create a complete, production-ready component with this vertical layout for EPS and Revenue columns.
