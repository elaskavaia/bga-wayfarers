import "./setup";
import { expect } from "chai";
import sinon from "sinon";
import { LocalProp, LocalSettings } from "../LocalSettings";

describe("LocalSettings", () => {
  const KEY = "wayfarers.zoomcontrols";
  let onChange: sinon.SinonSpy;
  let prop: LocalProp;

  const makeProp = (): LocalProp => ({
    key: "zoomcontrols",
    label: "Board zoom",
    choice: { controls: "Show zoom controls", fit: "Scale to fit" },
    default: "controls",
    onChange
  });

  const menuHtml = `
    <div id='ebd-body'></div>
    <div id='ingame_menu_content'>
      <div class='preference_choice' id='pref_a'></div>
      <div class='preference_choice' id='pref_b'></div>
    </div>`;

  beforeEach(() => {
    localStorage.clear();
    document.body.innerHTML = menuHtml;
    onChange = sinon.spy();
    prop = makeProp();
  });

  const selectOf = () => document.getElementById("localsettings_prop_zoomcontrols") as HTMLSelectElement;

  describe("setup", () => {
    it("applies the default when nothing is stored", () => {
      new LocalSettings("wayfarers", [prop]).setup();

      expect(prop.value).to.equal("controls");
      expect(onChange.calledOnceWith("controls")).to.be.true;
      expect($("ebd-body").dataset.localsetting_zoomcontrols).to.equal("controls");
    });

    it("does not persist the default, so a later change of default is picked up", () => {
      new LocalSettings("wayfarers", [prop]).setup();

      expect(localStorage.getItem(KEY)).to.be.null;
    });

    it("applies the stored value", () => {
      localStorage.setItem(KEY, "fit");

      new LocalSettings("wayfarers", [prop]).setup();

      expect(prop.value).to.equal("fit");
      expect(onChange.calledOnceWith("fit")).to.be.true;
    });

    it("falls back to the default when the stored value is no longer a choice", () => {
      localStorage.setItem(KEY, "bogus");

      new LocalSettings("wayfarers", [prop]).setup();

      expect(prop.value).to.equal("controls");
    });
  });

  describe("renderContents", () => {
    it("returns false when the menu is absent (local harness)", () => {
      document.body.innerHTML = "<div id='ebd-body'></div>";

      const settings = new LocalSettings("wayfarers", [prop]);
      settings.setup();

      expect(settings.renderContents("ingame_menu_content")).to.be.false;
    });

    it("lands after the last native preference", () => {
      const settings = new LocalSettings("wayfarers", [prop]);
      settings.setup();

      expect(settings.renderContents("ingame_menu_content")).to.be.true;
      expect(document.getElementById("pref_b")!.nextElementSibling!.id).to.equal(settings.getDivId());
    });

    it("preselects the current value", () => {
      localStorage.setItem(KEY, "fit");
      const settings = new LocalSettings("wayfarers", [prop]);
      settings.setup();
      settings.renderContents("ingame_menu_content");

      expect(selectOf().value).to.equal("fit");
    });

    it("replaces a previous copy instead of stacking one (setup re-runs on undo/reconnect)", () => {
      const settings = new LocalSettings("wayfarers", [prop]);
      settings.setup();
      settings.renderContents("ingame_menu_content");
      settings.renderContents("ingame_menu_content");

      expect(document.querySelectorAll(`#${settings.getDivId()}`).length).to.equal(1);
    });
  });

  describe("changing the control", () => {
    beforeEach(() => {
      const settings = new LocalSettings("wayfarers", [prop]);
      settings.setup();
      settings.renderContents("ingame_menu_content");
      onChange.resetHistory();
    });

    it("persists the pick and notifies the game", () => {
      const select = selectOf();
      select.value = "fit";
      select.dispatchEvent(new window.Event("change"));

      expect(localStorage.getItem(KEY)).to.equal("fit");
      expect(onChange.calledOnceWith("fit")).to.be.true;
      expect($("ebd-body").dataset.localsetting_zoomcontrols).to.equal("fit");
    });

    it("keeps taps to itself, so the BGA menu does not steal focus on mobile", () => {
      const outer = sinon.spy();
      document.getElementById("ingame_menu_content")!.addEventListener("click", outer);

      selectOf().dispatchEvent(new window.Event("click", { bubbles: true }));

      expect(outer.called).to.be.false;
    });
  });
});
