/**
 * Clean DOM tree.
 *
 * Removes the useless node from the DOM
 * (whitespace-only nodes, comments).
 *
 * @param {Node} node Node to be cleaned.
 *
 * @link https://www.sitepoint.com/removing-useless-nodes-from-the-dom/
 */
export default function cleanDOM(node) {
  for (let n = 0; n < node.childNodes.length; n++) {
    const child = node.childNodes[n];
    if (
      child.nodeType === 8 ||
      (child.nodeType === 3 && !/\S/.test(child.nodeValue))
    ) {
      node.removeChild(child);
      n--;
    } else if (child.nodeType === 1) {
      cleanDOM(child);
    }
  }
}
