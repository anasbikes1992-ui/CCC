/**
 * Keyboard Navigation Detection Utility
 * 
 * Detects when users are navigating with keyboard (Tab key) vs mouse
 * and adds appropriate classes to <body> for focus styling.
 * 
 * This improves UX by only showing prominent focus indicators for keyboard users,
 * while providing subtle focus states for mouse users.
 * 
 * WCAG 2.2 Criteria: 2.4.7 (Focus Visible), 2.4.11 (Focus Appearance)
 */

export function initKeyboardNavigation() {
  if (typeof window === 'undefined') return;

  // Track if user is currently using keyboard navigation
  let isTabbing = false;

  /**
   * Handle Tab key press - user is navigating with keyboard
   */
  function handleKeyDown(e: KeyboardEvent) {
    if (e.key === 'Tab' && !isTabbing) {
      isTabbing = true;
      document.body.classList.add('user-is-tabbing');
    }
  }

  /**
   * Handle mouse/touch interaction - user switched to pointer input
   */
  function handlePointerInput() {
    if (isTabbing) {
      isTabbing = false;
      document.body.classList.remove('user-is-tabbing');
    }
  }

  // Listen for Tab key
  document.addEventListener('keydown', handleKeyDown);

  // Listen for mouse/touch to remove keyboard mode
  document.addEventListener('mousedown', handlePointerInput);
  document.addEventListener('touchstart', handlePointerInput);

  // Cleanup function
  return () => {
    document.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('mousedown', handlePointerInput);
    document.removeEventListener('touchstart', handlePointerInput);
  };
}

/**
 * Focus Trap Utility
 * 
 * Traps focus within a container (useful for modals, dropdowns)
 * Ensures keyboard users can't tab out of the interactive element.
 * 
 * WCAG 2.2 Criteria: 2.1.2 (No Keyboard Trap), 2.4.3 (Focus Order)
 */
export function trapFocus(container: HTMLElement) {
  if (!container) return () => {};

  const focusableElements = container.querySelectorAll<HTMLElement>(
    'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
  );

  const firstElement = focusableElements[0];
  const lastElement = focusableElements[focusableElements.length - 1];

  function handleTabKey(e: KeyboardEvent) {
    if (e.key !== 'Tab') return;

    // Shift + Tab (backward)
    if (e.shiftKey) {
      if (document.activeElement === firstElement) {
        e.preventDefault();
        lastElement?.focus();
      }
    } 
    // Tab (forward)
    else {
      if (document.activeElement === lastElement) {
        e.preventDefault();
        firstElement?.focus();
      }
    }
  }

  container.addEventListener('keydown', handleTabKey);

  // Return cleanup function
  return () => {
    container.removeEventListener('keydown', handleTabKey);
  };
}

/**
 * Focus Management Utilities
 */

/**
 * Set focus to an element with optional scroll behavior
 */
export function setFocus(element: HTMLElement | null, preventScroll = false) {
  if (!element) return;
  
  element.focus({ preventScroll });
  
  // Announce to screen readers that focus has moved
  announceToScreenReader(`Focus moved to ${element.getAttribute('aria-label') || element.textContent || 'element'}`);
}

/**
 * Move focus to the first error in a form
 * Useful after form validation fails
 */
export function focusFirstError(formElement: HTMLFormElement) {
  const firstError = formElement.querySelector<HTMLElement>('[aria-invalid="true"]');
  if (firstError) {
    setFocus(firstError);
    return true;
  }
  return false;
}

/**
 * Announce message to screen readers using aria-live region
 */
export function announceToScreenReader(message: string, priority: 'polite' | 'assertive' = 'polite') {
  if (typeof window === 'undefined') return;

  // Find or create live region
  let liveRegion = document.getElementById('a11y-live-region');
  
  if (!liveRegion) {
    liveRegion = document.createElement('div');
    liveRegion.id = 'a11y-live-region';
    liveRegion.className = 'sr-only';
    liveRegion.setAttribute('aria-live', priority);
    liveRegion.setAttribute('aria-atomic', 'true');
    document.body.appendChild(liveRegion);
  }

  // Update priority if needed
  liveRegion.setAttribute('aria-live', priority);

  // Clear and set new message (forces screen reader announcement)
  liveRegion.textContent = '';
  setTimeout(() => {
    liveRegion!.textContent = message;
  }, 100);
}

/**
 * Skip to main content functionality
 * Used by skip navigation links
 */
export function skipToMain(mainId = 'main-content') {
  const main = document.getElementById(mainId);
  if (main) {
    main.focus();
    main.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

/**
 * Check if element is visible and focusable
 */
export function isFocusable(element: HTMLElement): boolean {
  if (!element) return false;

  // Check if element is visible
  const isVisible = element.offsetWidth > 0 && element.offsetHeight > 0;
  if (!isVisible) return false;

  // Check if element is disabled
  if (element.hasAttribute('disabled') || element.getAttribute('aria-disabled') === 'true') {
    return false;
  }

  // Check if element has negative tabindex
  const tabindex = element.getAttribute('tabindex');
  if (tabindex && parseInt(tabindex) < 0) {
    return false;
  }

  return true;
}

/**
 * Get all focusable elements within a container
 */
export function getFocusableElements(container: HTMLElement): HTMLElement[] {
  const selector = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
  const elements = Array.from(container.querySelectorAll<HTMLElement>(selector));
  return elements.filter(isFocusable);
}

/**
 * React hook for keyboard navigation detection
 */
export function useKeyboardNavigation() {
  if (typeof window === 'undefined') return;

  useEffect(() => {
    const cleanup = initKeyboardNavigation();
    return cleanup;
  }, []);
}

// For React projects, import useEffect
declare const useEffect: (effect: () => void | (() => void), deps: any[]) => void;
