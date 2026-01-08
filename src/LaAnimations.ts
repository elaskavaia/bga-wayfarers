class LaAnimations {
  public defaultAnimationDuration: number = 500;
  phantomMove(
    mobileId: ElementOrId,
    newparentId: ElementOrId,
    duration?: number,
    mobileStyle?: StringProperties,
    onEnd?: (node?: HTMLElement) => void
  ) {
    var mobileNode = $(mobileId) as HTMLElement;

    if (!mobileNode) throw new Error(`Does not exists ${mobileId}`);
    var newparent = $(newparentId);
    if (!newparent) throw new Error(`Does not exists ${newparentId}`);
    if (duration === undefined) duration = this.defaultAnimationDuration;
    if (!duration || duration < 0) duration = 0;
    const noanimation = duration <= 0 || !mobileNode.parentNode;
    const oldParent = mobileNode.parentElement;
    var clone = null;
    if (!noanimation) {
      // do animation
      clone = this.projectOnto(mobileNode, "_temp");
      mobileNode.style.opacity = "0"; // hide original
    }

    const rel = mobileStyle?.relation;
    if (rel) {
      delete mobileStyle.relation;
    }
    if (rel == "first") {
      newparent.insertBefore(mobileNode, null);
    } else {
      newparent.appendChild(mobileNode); // move original
    }

    setStyleAttributes(mobileNode, mobileStyle);
    newparent.classList.add("move_target");
    oldParent?.classList.add("move_source");
    mobileNode.offsetHeight; // recalc

    if (noanimation) {
      setTimeout(() => {
        newparent.offsetHeight;
        newparent.classList.remove("move_target");
        oldParent?.classList.remove("move_source");
        if (onEnd) onEnd(mobileNode);
      }, 0);
      return;
    }

    var desti = this.projectOnto(mobileNode, "_temp2"); // invisible destination on top of new parent
    try {
      //setStyleAttributes(desti, mobileStyle);
      clone.style.transitionDuration = duration + "ms";
      clone.style.transitionProperty = "all";
      clone.style.visibility = "visible";
      clone.style.opacity = "1";
      // that will cause animation
      clone.style.left = desti.style.left;
      clone.style.top = desti.style.top;
      clone.style.transform = desti.style.transform;
      // now we don't need destination anymore
      desti.parentNode?.removeChild(desti);
      setTimeout(() => {
        newparent.classList.remove("move_target");
        oldParent?.classList.remove("move_source");
        mobileNode.style.removeProperty("opacity"); // restore visibility of original
        clone.parentNode?.removeChild(clone); // destroy clone
        if (onEnd) onEnd(mobileNode);
      }, duration);
    } catch (e) {
      // if bad thing happen we have to clean up clones
      console.error("ERR:C01:animation error", e);
      desti.parentNode?.removeChild(desti);
      clone.parentNode?.removeChild(clone); // destroy clone
      //if (onEnd) onEnd(mobileNode);
    }
  }

  getFulltransformMatrix(from: Element, to: Element) {
    let fullmatrix = "";
    let par = from;

    while (par != to && par != null && par != document.body) {
      var style = window.getComputedStyle(par as Element);
      var matrix = style.transform; //|| "matrix(1,0,0,1,0,0)";

      if (matrix && matrix != "none") fullmatrix += " " + matrix;
      par = par.parentNode as Element;
      // console.log("tranform  ",fullmatrix,par);
    }

    return fullmatrix;
  }

  projectOnto(from: ElementOrId, postfix: string, ontoWhat?: ElementOrId) {
    const elem: Element = $(from);
    let over: Element;
    if (ontoWhat) over = $(ontoWhat);
    else over = $("oversurface"); // this div has to exists with pointer-events: none and cover all area with high zIndex
    var elemRect = elem.getBoundingClientRect();

    //console.log("elemRect", elemRect);

    var newId = elem.id + postfix;
    var old = $(newId);
    if (old) old.parentNode.removeChild(old);

    var clone = elem.cloneNode(true) as HTMLElement;
    clone.id = newId;
    clone.classList.add("phantom");
    clone.classList.add("phantom" + postfix);
    clone.style.transitionDuration = "0ms"; // disable animation during projection
    if (elemRect.width > 1) {
      clone.style.width = elemRect.width + "px";
      clone.style.height = elemRect.height + "px";
    }

    var fullmatrix = this.getFulltransformMatrix(elem.parentNode as Element, over.parentNode as Element);

    over.appendChild(clone);
    var cloneRect = clone.getBoundingClientRect();

    const centerY = elemRect.y + elemRect.height / 2;
    const centerX = elemRect.x + elemRect.width / 2;
    // centerX/Y is where the center point must be
    // I need to calculate the offset from top and left
    // Therefore I remove half of the dimensions + the existing offset
    const offsetX = centerX - cloneRect.width / 2 - cloneRect.x;
    const offsetY = centerY - cloneRect.height / 2 - cloneRect.y;

    // Then remove the clone's parent position (since left/top is from tthe parent)
    //console.log("cloneRect", cloneRect);

    // @ts-ignore
    clone.style.left = offsetX + "px";
    clone.style.top = offsetY + "px";
    clone.style.transform = fullmatrix;
    clone.style.transitionDuration = undefined;

    return clone;
  }
}

function setStyleAttributes(element: HTMLElement, attrs: { [key: string]: string }): void {
  if (attrs !== undefined) {
    Object.keys(attrs).forEach((key: string) => {
      element.style.setProperty(key, attrs[key]);
    });
  }
}
